1. Установка postgresql через helm: 
2. kubectl apply -f pv.yaml -f pvc.yaml
3. helm repo add bitnami https://charts.bitnami.com/bitnami
4. helm repo update
5. helm install postgresql-dev -f pgsql-values.yaml bitnami/postgresql --set volumePermissions.enabled=true
6. helm install otus-app otus/
7. kubectl port-forward --namespace=m service/nginx-ingress-nginx-controller 8000:80
8. Для подключения к БД вне кластера: export POSTGRES_PASSWORD=$(kubectl get secret --namespace default postgresql-dev -o jsonpath="{.data.password}" | base64 -d) && kubectl port-forward --namespace default svc/postgresql-dev 5432:5432 &
   PGPASSWORD="$POSTGRES_PASSWORD" psql --host 127.0.0.1 -U otus -d otus -p 5432

CRUD:
1. Запрос на создание пользователя: POST http://arch.homework:8000/api/users
  {
   "name": "Petr",
   "email": "petr1974@test.ru",
   "password": "1234"
   }
2. Получение всех пользователей: GET http://arch.homework:8000/api/users
3. Обновление пользователя: PUT http://arch.homework:8000/api/users/1
   {
   "id: 1,
   "name": "Alex",
   "email": "alex1974@test.ru",
   "password": "1234"
   }
4. Удаление пользователя: DELETE http://arch.homework:8000/api/users/1
   