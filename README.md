  
    
### Микросервис сбора ID пользователей из социальных сетей.      
 Компонент по сбору уникальный идентификаторов пользователей из социальных сетей.      
Представляет собой приложение написанное на фреймворке Lumen (https://lumen.laravel.com/).      
      
Начинает со стартового пользователя и пытается скачать N друзей этого пользователя. Если друзья кончаются, берётся      
 случайный пользователей из ранее сохранённых и процесс начинается снова, пока не будет достигнуто добавление N друзей.      
       
Микросервис не сохраняет и удаляет из базы пользователей, у которых профиль закрыт настройками приватности,       
аккаунт удален или пользователей не из России.      
      
По умолчанию N = 1000, число можно редактировать параметром.      
```sql CREATE TABLE `friends` (      
  `id` int(11) NOT NULL,      
  `social_network_name` varchar(14) COLLATE utf8_unicode_ci NOT NULL,      
  `parsed` tinyint(4) NOT NULL,      
  `created_at` timestamp NULL DEFAULT NULL,  `updated_at` timestamp NULL DEFAULT NULL, PRIMARY KEY (`id`),      
 KEY `friends_parsed_index` (`parsed`),      
 KEY `friends_created_at_index` (`created_at`),      
 KEY `friends_updated_at_index` (`updated_at`),      
 KEY `friends_created_at_parsed_index` (`created_at`,`parsed`),      
 KEY `friends_updated_at_parsed_index` (`updated_at`,`parsed`),      
KEY `friends_social_network_name_index` (`social_network_name`), KEY `friends_id_social_network_name_index` (`id`,`social_network_name`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; ``` Пояснение по полям:      
      
|Поле   | Описание  |
 | :------------ | :------------ |  
| id  | ID пользователя ВК   |  
| social_network_name  | Название социальной сети   |  
|  parsed |флаг обработки для следующего компонента в очереди   | |  created_at | дата создания записи  | |  updated_at | дата обновления записи  |   
  ### Локальная установка  
1. Убедиться, что стоит php 7.2 и настроена база данных Mysql.      
2. Клонировать данный репозиторий и войти в него      
3. Установить зависимости ```composer install``` 4. В файле ```.env``` прописать опции доступа к БД и данные от приложения ВК.       
      
### Запуск и опции 1. Запустить миграции для заполнения базы данных ```php artisan migrate```  
2. Запустить php-сервер: ```php -S localhost:8000 -t public```  
3. Открыть страницу:      
http://localhost:8000/grabIds      
  
Можно управлять количеством загружаемых профилей:      
http://localhost:8000/grabIds/250, где 250 - число новых VK ID для сохранения.      
      
### REST API      
|Метод   | Url  | Описание |  Параметры |
| :------------ | :------------ | :------------ |  :------------ | 
|GET|/v1/getParsedIdsCount|Метод возвращяет количество распарсенных ID|---|
|GET|/v1/getUserIds/```count```|Метод возвращяет  ```count``` самых старых ID<br>Формат ответа:```{"status":200,"message":"Users' ids fetched successfully","parameters":{"count":"2","skip":0},"body":[{"id":17979,"social_network_name":"vk"},{"id":394450,"social_network_name":"vk"}]}```|```count``` - количество записей  <br>GET-параметр ```skip``` - сколько записей пропустить|
|PUT|/v1/checkUserWithRemove/```social-network-name```/```user-id```|Метод проверяет, может ли мы собрать фото для пользователя ```user-id``` в социальной сети ```social-network-name```, и если нет - то удаляет ID пользователя из базы|```social-network-name``` - имя социальной сети  <br>```user-id``` - ID пользователя в указанной социальной сети|
|PATCH|/v1/markUserAsParsed/```social-network-name```/```user-id```|Метод помечает пользователя ```user-id``` в социальной сети ```social-network-name``` как отпарсенного.|```social-network-name``` - имя социальной сети  <br>```user-id``` - ID пользователя в указанной социальной сети|
|PATCH|/v1/markUserAsParsed/```social-network-name```/```user-id```|Метод помечает пользователя ```user-id``` в социальной сети ```social-network-name```как неактивного.|```social-network-name``` - имя социальной сети  <br>```user-id``` - ID пользователя в указанной социальной сети|