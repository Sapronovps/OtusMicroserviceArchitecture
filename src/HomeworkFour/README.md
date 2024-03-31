# Четвертое домашнее задание

### Установка postgresql через helm
1. Добавим репозиторий: 
> helm repo add bitnami https://charts.bitnami.com/bitnami

> helm repo update

2. Создадим pv и pvc:
> kubectl apply -f pv.yaml -f pvc.yaml

3. Установим postgresql с собственным values.yaml:
> helm install postgresql-dev -f pgsql-values.yaml bitnami/postgresql --set volumePermissions.enabled=true

4. Если нужен доступ из вне кластера:
> export POSTGRES_PASSWORD=$(kubectl get secret --namespace default postgresql-dev -o jsonpath="{.data.password}" | base64 -d) && kubectl port-forward --namespace default svc/postgresql-dev 5432:5432 &
PGPASSWORD="$POSTGRES_PASSWORD" psql --host 127.0.0.1 -U otus -d otus -p 5432

### Установка приложения через helm
> helm install otus-app otus/

> kubectl port-forward --namespace=m service/nginx-ingress-nginx-controller 8000:80

## CRUD приложения для создания пользователей (Примеры запросов):
1. Запрос на создание пользователя:
> <b>POST</b> http://arch.homework:8000/api/users <br>
> headers: content-type: application/json <br><br>
> {"name": "Petr","email": "petr1974@test.ru","password": "1234"}

2. Получение всех пользователей:
> <b>GET</b> http://arch.homework:8000/api/users
3. Обновление пользователя:
> <b>PUT</b> http://arch.homework:8000/api/users/1 <br>
> headers: content-type: application/json <br><br>
   {
   "id: 1,
   "name": "Alex",
   "email": "alex1974@test.ru",
   "password": "1234"
   }
4. Удаление пользователя:
> <b>DELETE</b> http://arch.homework:8000/api/users/1
   