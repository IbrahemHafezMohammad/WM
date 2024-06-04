
## About WM Projects
This is WM projects.
## Development Mode Links are here 
- Admin panel Links (https://wmadmin-staging.iegaming.io)
- username : harry   and pass : 1234qweR;
- User Panel Links (https://wmplayer-staging.iegaming.io/login)
- username :   gameplayer0008 and pass : 1234qweR;

## Run this project Commands 

- Clone It :  git clone https://github.com/iesofttech/PM-BO-backend.git
- cp .env.example .env
- php artisian key:generate
- Create database and set database name is .env
- php artisan migrate:fresh --seed
- php artisan serve

## Run The These Command to test the TestCases 
- We are Testing with same database so you see Data in BO Admin Dashboard 
## Auththentication TestCases | Login | Register

- \vendor\bin\phpunit .\tests\Feature\Auth : done 
## PaymentService Test | Deposit | Withdraw
- \vendor\bin\phpunit .\tests\Feature\Payment\DepositTest.php  : In process
- \vendor\bin\phpunit .\tests\Feature\Payment\WithdrawTest.php : In process

