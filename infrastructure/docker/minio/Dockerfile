FROM golang:1.24-alpine AS builder

ARG MINIO_REF=RELEASE.2025-09-06T17-38-46Z

RUN apk add --no-cache git
WORKDIR /src

RUN git clone --depth=1 --branch "${MINIO_REF}" https://github.com/minio/minio.git .
RUN CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /out/minio .

FROM alpine:3.21

RUN apk add --no-cache ca-certificates
COPY --from=builder /out/minio /usr/local/bin/minio

ENTRYPOINT ["minio"]
