# Documentation screenshots

`screenshots.sh` creates the backend screenshots in `Documentation/Images/` with Playwright
(Docker image `mcr.microsoft.com/playwright`, version pinned) against the DDEV instance v14
(TYPO3 14.3). The browser runs with a light theme and a 1440x900 viewport and logs in with the form.

The container joins the Docker network `ddev_default`, where the DDEV router answers for the
project hostnames, so `https://v14.rsmbouncemailprocessor.ddev.site` works unchanged (the
certificate is ignored).

## 1. Prepare the data

```
ddev start
ddev install-v14                    # clean instance
ddev bounces --purge
ddev bounces --email=recipient1@example.test --rcptuid=1 --sendout=1 --times=2 550-user-unknown.eml
ddev bounces --email=recipient2@example.test --rcptuid=2 --sendout=1 551-mailbox-full.eml out-of-office.eml
ddev bounces --email=recipient3@example.test --rcptuid=3 --sendout=1 spam.eml
ddev bounces --email=recipient4@example.test --rcptuid=4 --sendout=1 exchange-user-unknown.eml
ddev bounces --email=recipient4@example.test --rcptuid=4 --sendout=1 550-user-unknown.eml
ddev bounces --email=recipient5@example.test --rcptuid=5 --sendout=1 out-of-office.eml
ddev exec "cd /var/www/html/v14 && vendor/bin/typo3 scheduler:run --task=1 -f"   # analyze
ddev exec "cd /var/www/html/v14 && vendor/bin/typo3 scheduler:run --task=2 -f"   # process
```

The seed has one newsletter. For two newsletters in the bounce report, copy the newsletter
and sendout record (uid 2, set `sendout` 2 in `--sendout=2`) and give both a title, a sending
time and the totals of the sendout. Recipient1 and recipient4 are removed by task 2, so the
recipient report shows the status "removed".

## 2. Create the screenshots

```
Build/Screenshots/screenshots.sh
```

Optional environment variables: `BASE_URL`, `BE_USER`, `BE_PASSWORD`, `NEWSLETTER_PAGE`
(page of the newsletters, default 2). Only `example.test` addresses appear in the images.
The password field of the scheduler form must stay empty in the image (it is, because the
stored password is never sent to the browser).
