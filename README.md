# php-crypto-trading-bot
GUI Screenshots:

![alt text](https://ground-control.io/bot1.JPG)
![alt text](https://ground-control.io/bot2.JPG)
![alt text](https://ground-control.io/bot3.JPG)
![alt text](https://ground-control.io/bot4.JPG)


Telegram screenshot:

![alt text](https://ground-control.io/telegram.JPG)

Install using SSH:

Install trader.so php ext and add to php.ini:

extension=trader.so

trader.real_precision=8


cd /

git clone https://github.com/tomthedude/php-crypto-trading-bot-v2


Edit conf.json:

Add your Binance / Bittrex key and secret (for real mode only)


import mysql database + edit init.php for db credentials


Run:

php main.php


The Bot GUI should be availabe at ip:8080

user: Admin

pass: 1234


mkdir msgs_from_web
mkdir msgs_to_web
mkdir cmnds
mkdir simulations