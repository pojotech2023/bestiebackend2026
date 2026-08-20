#!/bin/bash
(crontab -l | grep -v "/usr/local/bin/ea-php82 /home/bestieco/public_html/artisan dm:disbursement") | crontab -

(crontab -l | grep -v "/usr/local/bin/ea-php82 /home/bestieco/public_html/artisan restaurant:disbursement") | crontab -

