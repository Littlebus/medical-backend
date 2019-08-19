# -*- coding: utf-8 -*-
"""
Created on Mon Sep 18 14:45:44 2017

@author: Administrator
"""

import math
from sklearn.metrics import mean_squared_error
import sys
import json

class Kalman(object):
    def __init__(self):
        self._A = 1.0
        self._B = 0.0
        self._H = 1.0
        self._P = 0.0
        self._Q = 0.2
        self._R = 0.8
        
        self.length = 30
        self.predictedStateEstimate = 0.0
        self.currentStateEstimate = 0.0
        self.predictedProbEstimate = 0.0
        self.currentProbEstimate = self._P
        self.innovation = 0.0
        self.innovationCovariance = 0.0
        self.history = []
        self.weight = 0.2
    
    def updateMetric(self, metric):
        self.kalman(metric)
        self.history.append(metric)
        if len(self.history) > self.length:
            self.history = self.history[len(self.history) - self.length:]
    
    def kalman(self, metric):
        control = 0
        self.predictedStateEstimate = self._A * self.currentStateEstimate + self._B * control
        self.predictedProbEstimate = self._A * self.currentProbEstimate * self._A + self._Q
        self.innovation = metric - self._H * self.predictedStateEstimate
        self.innovationCovariance = self._H * self.predictedProbEstimate * self._H + self._R
        kalmanGain = self.predictedProbEstimate * self._H / self.innovationCovariance
        self.currentStateEstimate = self.predictedStateEstimate + kalmanGain * self.innovation
        self.currentProbEstimate = (1 - kalmanGain * self._H) * self.predictedProbEstimate + self.weight * self._R
    
    def predicted(self):
        return self.currentStateEstimate

class KalmanFilter(object):
    def average_y(self, test_y):
        return float(sum(test_y)) / len(test_y)
    
    def y_errors(self, test_y, predict_y):
        return math.sqrt(mean_squared_error(test_y, predict_y))

    def percent_y_errors(self, test_y, predict_y):
        s = 0.0
        cnt = 0
        for i in range(0, len(predict_y)):
            err = float(predict_y[i]) - float(test_y[i])
            s += abs(err / test_y[i])
            cnt += 1.0
        if cnt == 0.0:
            return -1.0
        return s / cnt
    
    # def train(self, ys, look_back = 10):
    #     kalman = Kalman()
    #     predict_y = []
    #     for y in ys:
    #         kalman.updateMetric(y)
    #         predict_y.append(kalman.predicted())
    #
    #     x = [i for i in range(0, len(ys))]
    #     plt.plot(x, ys, label = 'origin')
    #     plt.plot(x, predict_y, label = 'kalman')
    #     print ys
    #     print predict_y
    #     plt.xlabel("time / month(from 2008-01)")
    #     plt.ylabel("total fee / yuan")
    #     plt.legend(loc = 'upper left')
    #     plt.show()
    #     plt.close()
    #
    #
    #     ys = ys[look_back:]
    #     predict_y = predict_y[look_back:]
    #     return [self.average_y(ys), self.average_y(predict_y), self.y_errors(ys, predict_y), self.percent_y_errors(ys, predict_y)]
    
    def predict_one(self, ys):
        kalman = Kalman()

        for y in ys:
            kalman.updateMetric(y)
            y_predict = kalman.predicted()
        return y_predict

f = open(sys.argv[1], "r")
input_str = f.read()
f.close()
input = json.loads(input_str)
input = [int(input["p1"]), int(input["p2"]), int(input["p3"]), int(input["p4"]), int(input["p5"])]

ret = {}
ret["result"] = KalmanFilter().predict_one(input)
f = open(sys.argv[1] + "_result", "w")
ret["success"] = True
f.write(json.dumps(ret))
f.close()

# if __name__ == "__main__":
#     y = load_data_from_y_file("data/months/ys.txt")
#     f = KalmanFilter()
#     f.train(y)