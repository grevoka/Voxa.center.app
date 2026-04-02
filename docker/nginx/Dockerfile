FROM nginx:1.25-alpine

LABEL maintainer="SIP Project"

RUN rm /etc/nginx/conf.d/default.conf

COPY nginx.conf /etc/nginx/nginx.conf
COPY conf.d/app.conf /etc/nginx/conf.d/app.conf

EXPOSE 80 443
