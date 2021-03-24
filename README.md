# php-crypto-trading-bot
GUI Screenshots:

![bot1](https://user-images.githubusercontent.com/22947607/112216917-b9e15700-8c2a-11eb-930e-61a3866a3e7e.jpg)
![bot2 (1)](https://user-images.githubusercontent.com/22947607/112216994-d4b3cb80-8c2a-11eb-9229-be6ea0cfaf24.jpg)
![bot3](https://user-images.githubusercontent.com/22947607/112217044-e301e780-8c2a-11eb-8aec-896ac8500294.jpg)
![bot4](https://user-images.githubusercontent.com/22947607/112217098-f0b76d00-8c2a-11eb-80ba-1738274c07af.jpg)


Telegram screenshot:

![telegram](https://user-images.githubusercontent.com/22947607/112217179-0167e300-8c2b-11eb-9821-7bc8b3a205d2.jpg)

Install using SSH:

Install trader.so php ext and add to php.ini:

extension=trader.so

trader.real_precision=8


cd /

git clone https://github.com/tomthedude/web-sockets-crypto-trading-bot-php.git


Edit my_conf.json:

Add your Binance / Bittrex key and secret (for real mode only)


import mysql database + edit init.php for db credentials

edit log.php to credentials


Run:

php main.php
or
console.php to start all in background or kill bot


The Bot GUI should be availabe at ip:8080

user: Admin

pass: 1234


mkdir msgs_from_web


mkdir msgs_to_web


mkdir cmnds


mkdir simulations
