Перейти в папку с репозиторием и пследовательно выполнить следующие команды:
```shell
make init
docker exec -ti sio_test_php bash
composer install
bin/console doctrine:migrations:migrate
bin/console doctrine:fixtures:load
```

После этого сайт будет доступен по адресу: https://localhost:65443

Автособираемая документация к API: https://localhost:65443/api/v1_doc

По команде `bin/console app:generate-api-library` можно собрать js-библиотеку, которая лежит в папке /public/api.js

-------------

# Написать Symfony REST-приложение для расчета цены продукта и проведения оплаты

Необходимо реализовать 2 эндпоинта:
1. POST: для расчёта цены

http://127.0.0.1:80/calculate-price

Пример JSON тела запроса:
```
{
    "product": 1,
    "taxNumber": "DE123456789",
    "couponCode": "D15"
}
```
2. POST: для осуществления покупки

http://127.0.0.1:80/purchase

Пример JSON тела запроса:
```
{
    "product": 1,
    "taxNumber": "IT12345678900",
    "couponCode": "D15",
    "paymentProcessor": "paypal"
}
```

При успешном выполнении запроса следует возвращать HTTP ответ с кодом 200.

При неверных входных данных или ошибках оплаты следует возвращать HTTP ответ с кодом 400 и JSON объект с описанием ошибок.

## Продукты
Продукты предполагается хранить в БД. В качестве примера можно использовать 3 продукта:
- Iphone (100 евро)
- Наушники (20 евро)
- Чехол (10 евро)

## Купоны
Купоны позволяют применить скидку к покупке и могут быть двух типов:
- фиксированная сумма скидки
- процент от суммы покупки

Предполагается, что купоны создаются продавцом и хранятся в системе. Например, при наличии купонов P10 (скидка 10%) и P100 (скидка 100%) покупатель не должен иметь возможности применить несуществующий купон P50.

Формат кода купона вы можете выбрать на своё усмотрение.

## Расчет налога
Налог рассчитывается исходя из страны налогового номера и прибавляется к цене продукта:
- Германия - 19%
- Италия - 22%
- Франция - 20%
- Греция - 24%

Например, цена Iphone для покупателя из Греции составит 124 евро (100 евро + налог 24%). Если у покупателя есть купон на 6% скидку, то цена будет 116.56 евро (100 евро - 6% скидка + налог 24%).

## Формат налогового номера
Форматы налоговых номеров для разных стран:
- DEXXXXXXXXX - Германия
- ITXXXXXXXXXXX - Италия
- GRXXXXXXXXX - Греция
- FRYYXXXXXXXXX - Франция

где X - любая цифра от 0 до 9, Y - любая буква. Длина налогового номера различается в зависимости от страны.

## Начало работы
Для удобства выполнения и проверки задания предлагаем клонировать этот репозиторий. Это даст доступ к базовому Docker-контейнеру с необходимыми для задания компонентами. Для установки выполните `make init`, что запустит Symfony-приложение на порту 8337, смонтировав текущую директорию в контейнер.

Имейте в виду: контейнер предназначен для Linux и Mac. Для Windows потребуется WSL. Работая с нами, вы будете использовать контейнеры с аналогичными требованиями.

## Детали выполнения
При выполнении задания необходимо:
- реализовать валидацию всех полей запроса, включая корректность налогового номера, используя Symfony Validator
- рассчитать итоговую цену с учетом купона (если применим) и налога
- использовать `PaypalPaymentProcessor::pay()` или `StripePaymentProcessor::processPayment()` для проведения платежа. Эти классы представлены в проекте и не должны модифицироваться.
- обновить `requests.http`, демонстрируя логику работы вашего приложения.

CRUD для сущностей предполагается уже реализованным, дополнительные проверки в сервисах не требуются.

По завершении отправьте ссылку на репозиторий.

Учитывайте возможность добавления новых платежных процессоров.

Если какая-то часть задания кажется сложной, выберите простейшее решение и комментируйте альтернативные подходы, которые рассматривали.

### Будет плюсом
- Расширенная контейнеризация
- Использование сущностей, PostgreSQL/MySQL
- Наличие PHPUnit тестов
- Соответствие кода принципам SOLID
- Поэтапное оформление коммитов
- Показать способность избегать сложных подходов вроде onion-based/DDD/CQS/гексагональной архитектуры в пользу корректности и полноты решения.
