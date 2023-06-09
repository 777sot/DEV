# MySQL - 5.7

SELECT
    users.id AS ID,
    CONCAT(users.first_name, ' ',  users.last_name) AS NAME,
    books.author AS Authors,
    GROUP_CONCAT(books.name SEPARATOR ', ') AS Books
FROM users, user_books, books
WHERE
   users.id IN (SELECT users.id FROM user_books, users WHERE users.id = user_books.user_id GROUP BY users.id HAVING COUNT(users.id) = 2)
   # Выбираем пользователей которыу взяли всего 2 книги
   && YEAR(CURRENT_DATE()) - YEAR(birthday) >= 7
   && YEAR(CURRENT_DATE()) - YEAR(birthday) < 17 # Возраст пользователя в диапозоне от 7 до 17
   && DATEDIFF(user_books.get_date, user_books.return_date) <= 14 # Возврат книги не позднее 2-х недель (14 дней)
   && users.id = user_books.user_id #
   && books.id = user_books.book_id # Выборка данных из всех таблиц согласно фильтра данных
GROUP BY users.id, books.author
HAVING COUNT(`Authors`) = 2 # Книги одного автора в кол-ве 2 шт.

______________________________________________________________________________________________________________________

SELECT
        u.id AS ID,
        CONCAT(u.first_name, ' ', u.last_name) AS NAME,
        b.author AS Authors,
        GROUP_CONCAT(b.name SEPARATOR ', ') AS Books
FROM
        users u JOIN books b JOIN user_books ub
WHERE
        u.id = ub.user_id
        && YEAR(CURRENT_DATE()) - YEAR(u.birthday) >= 7
        && YEAR(CURRENT_DATE()) - YEAR(u.birthday) < 17
        && DATEDIFF(ub.get_date, ub.return_date) <= 14
        && u.id = ub.user_id
        && b.id = ub.book_id
GROUP BY
        u.id, b.author HAVING COUNT(u.id) = 2