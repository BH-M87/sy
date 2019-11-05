#!/bin/bash
step=3 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(curl localhost:9005/command/iot-face >> /data/fczl-backend/www/api/iot-face.txt)
    sleep $step
done

exit 0