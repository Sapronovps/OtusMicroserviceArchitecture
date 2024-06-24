# 1. ADR: Миграция приложения складского учета на распределенную архитектуру.

Дата: 2024-06-07

## Статус

Принят

## Контекст

На данный момент складской учет представляет собой монолитное приложение, которое включает
в себя такие бизнес-функции как управлением склада, управление магазином, оплатой, отправкой уведомлений.
Текущее приложение имеет множество проблем, связанных с масштабируемостью, доступностью, адаптируемостью и
сопровождаемостью.

## Решение

Миграция текущего монолитного приложения складского учета на распределенную систему. Переход к
распределенной системе даст следующие преимущества:

* Сделает функцию резерва более доступной для клиентов и тем самым обеспечит более высокую отказоустойчивость.
* Обеспечит повышенную масштабируемость при увеличении количества клиентов, что избавит от частых зависаний приложения.
* Отделит функциональность складского и магазинного жизненного цикла товара, что облегчит нагрузку н базу данных.
* Поможет увеличить скорость выпуска новых функций на рынок, в сравнении с монолитным приложением, что обеспечит общую гибкость.
* При падении сервиса склада, сервис магазина продолжит работать в штатном режиме.
* У каждого сервиса будет свой набор тестов, что увеличит скорость CI/CD и выпуска новых реализаций.

## Последствия 

Миграция требует разделить монолитную базу данных.

В период миграции будет практически запрещено внедрять новый функционал или он будет поставляться 
с задержками.

Миграция влечет дополнительные финансовые затраты.

Требуется подготовить новую инфраструктуру для распределенного приложения.

Настроить наблюдаемость: мониторинг, логирование, метрики, трассировка.