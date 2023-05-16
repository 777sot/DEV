# DEV
Каталок TEST_MYSQL :
    в файле SQL_SELECT_DB запрос выборки данных из представленных таблиц
    developer.sql Дамп базы данных 
    
Для написания Api использовался фрейвок Symfony

запуск проекта:

1. composer install
2. php bin/console doctrine:database:create (создание db) mysql 5.7, user = root,  no password
3. php bin/console doctrine:migrations:migrate (выполнение миграции)
4. php bin/console doctrine:fixtures:load (загрузка тестовых данных)

Отправка и тестирование : postman (отправка token в заголовках)

 key ->  AUTH-TOKEN                
 Value -> eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2ODQxNzkzMTQsImlzcyI6Imh0dHBzOi8vd3d3Lmdvb2dsZS5ydS8iLCJuYmYiOjE2ODQxNzkzMTQsImV4cCI6MTY4NTk5MzcxNCwia2V5IjoiVDYwOFBNMWduVCJ9.5pBQQSINaE3Nk00nPjtmhnB82jMXRYlJNy2Mjdqnj0c
 
 Валидация token производится по $secretKey (user password)
 Также возможно произвести валидацию по сроку использования token
 
 Token формировался :
 
 1. 'iat' => время создания
 2. 'iss' => издатель токена (serverName)
 3. 'nbf' => время начала дествия token
 4. 'exp' => дата истечения срока действия
 5. 'key' => пароль (secretKey)

 