@echo off
cd /d C:\Users\USER\Herd\sistema-facturacion

C:\Users\USER\.config\herd\bin\php84\php.exe artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=120