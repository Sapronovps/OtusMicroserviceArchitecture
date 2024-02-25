### Запуск образа с docker hub: 
> docker run -p 8000:80 sapronovps/otus-microservice-architecture:latest
### Сборка образа: 
> docker build --platform=linux/amd64 -t application .
### Запуск образа: 
> docker run -p 8000:80 application