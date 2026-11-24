<?php
// Legt die Scheduler-Tasks der Extension in der aktuellen TYPO3-Instanz an (Aufruf im Instanzverzeichnis).
// Idempotent: vorhandene Tasks werden vorher entfernt (die Test-Instanz enthaelt nur diese Tasks).
// Zugangsdaten stammen aus den BOUNCE_* Variablen (reine lokale Test-Werte aus docker-compose.web.yaml).

use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use RSM\Rsmbouncemailprocessor\Task\ProcessBounceMail;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Service\TaskService;

// Stuendlich wiederkehrende Ausfuehrung (gleiche API in TYPO3 13 und 14)
function recurring(): Execution
{
    $execution = new Execution();
    $execution->setStart(time());
    $execution->setInterval(3600);
    $execution->setEnd(0);
    $execution->setMultiple(false);
    $execution->setCronCmd('');
    return $execution;
}

$classLoader = require getcwd() . '/vendor/autoload.php';
SystemEnvironmentBuilder::run(1, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
// Boot wie vendor/bin/typo3 (Failsafe-Init, danach ext_localconf und ext_tables laden)
$bootService = Bootstrap::init($classLoader, true)->get(BootService::class);
$container = $bootService->getContainer();
$bootService->loadExtLocalconfDatabaseAndExtTables();
// DataHandler (TYPO3 14) braucht einen Backend-User, hier der CLI-User wie bei vendor/bin/typo3
Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
Bootstrap::initializeBackendAuthentication();

$connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_scheduler_task');
$connection->truncate('tx_scheduler_task');

$repository = $container->get(SchedulerTaskRepository::class);

$analyze = new AnalyzeBounceMail();
$analyze->setServer(getenv('BOUNCE_HOST') ?: 'greenmail');
$analyze->setPort((int)(getenv('BOUNCE_IMAP_PORT') ?: 3143));
$analyze->setUser(getenv('BOUNCE_USER') ?: 'bounce');
$analyze->setPassword(getenv('BOUNCE_PASSWORD') ?: 'bounce');
$analyze->setService('imap');
$analyze->setMaxProcessed(100);
$analyze->setDeletealways(false);
$analyze->setDescription('Bounce-Postfach (GreenMail) auswerten');
$analyze->setExecution(recurring());

$process = new ProcessBounceMail();
$process->setDescription('Bounce-Reports auswerten, Empfaenger entfernen');
$process->setExecution(recurring());

foreach ([$analyze, $process] as $task) {
    if ((new Typo3Version())->getMajorVersion() >= 14) {
        // TYPO3 14 speichert Tasks als tasktype + JSON. Der Repository-Weg (DataHandler) validiert wie ein
        // Backend-Formular (start/frequency), daher hier direkt in die Tabelle schreiben.
        $fields = $container->get(TaskService::class)->getFieldsForRecord($task);
        $fields['pid'] = 0;
        $fields['crdate'] = time();
        $connection->insert('tx_scheduler_task', $fields);
        $task->setTaskUid((int)$connection->lastInsertId());
    } elseif (!$repository->add($task)) {
        fwrite(STDERR, 'Task konnte nicht angelegt werden: ' . get_class($task) . "\n");
        exit(1);
    }
    echo get_class($task) . ' angelegt, uid=' . $task->getTaskUid() . "\n";
}
