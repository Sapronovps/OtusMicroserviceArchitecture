# Третье домашнее задание

### Создание namespace и установка nginx controller
> kubectl create namespace m && helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx/ && helm repo update && helm install nginx ingress-nginx/ingress-nginx --namespace m -f 04_nginx-ingress.yaml 

### Применение всех манифестов (Deployment, Service, Ingress)
> kubectl apply -f 01_deployment.yaml -f 02_service.yaml -f 03_ingress.yaml

### Проброс порта
> kubectl port-forward --namespace=m service/nginx-ingress-nginx-controller 8000:80

### Проверка работоспособности
> curl --resolve arch.homework:8080:127.0.0.1 http://arch.homework:8000
