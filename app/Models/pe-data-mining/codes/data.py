import numpy as np
import os
import pandas as pd

def is_nan(a):
    return str(a) == 'nan'


class Data:
    def _config(self):
        self.basic1 = "basic2016.csv"
        self.basic2 = "basic2017.csv"
        self.examine1 = "examine2016.csv"
        self.examine2 = "examine2017.csv"
        self.diagnosis1 = "diagnosis2016.csv"
        self.diagnosis2 = "diagnosis2017.csv"

        self.save_namelist = "namelist.npy"
        self.save_examine_list = "examine_list.npy"
        self.save_examine_list_values = "examine_list_%d.npy"

        self.basic_list = ["体检号","出生日期","性别","身高（cm）","体重（Kg）","BMI","血压(mmHg)","是否吸烟","早发心血管病家族史","内科病史","胸廓","呼吸音","心率", "甲状腺","乳腺","眼底(右)","眼底(左)","鼻"]

        self.need_normalize = ["是否吸烟"]


    def __init__(self, data_path, save_path):
        self.X = np.array([])
        self.Y = np.array([])
        self.data_path = data_path
        self.save_path = save_path
        self._config()

    def load_namelist(self, load_from_save=True, save=True):
        save_path = os.path.join(self.save_path, self.save_namelist)
        if load_from_save and os.path.exists(save_path):
            namelist = np.load(save_path)
        else:
            basic1 = pd.read_csv(os.path.join(self.data_path, self.basic1), encoding='gbk')
            basic2 = pd.read_csv(os.path.join(self.data_path, self.basic2), encoding='gbk')
            namelist = np.array(list(set(basic1["体检号"]).intersection(set(basic2["体检号"]))))

            if save:
                np.save(save_path, namelist)

        return namelist

    def load_examined_list(self, namelist, load_from_save=True, save=True):
        save_path = os.path.join(self.save_path, self.save_examine_list)
        if load_from_save and os.path.exists(save_path):
            examined = dict()
            keys = np.load(save_path)
            for i, k in enumerate(keys):
                examined[k] = set(np.load(os.path.join(self.save_path, self.save_examine_list_values).replace("%d", str(i))).tolist())
        else:
            examine1 = pd.read_csv(os.path.join(self.data_path, self.examine1), encoding='gbk')
            examine2 = pd.read_csv(os.path.join(self.data_path, self.examine2), encoding='gbk')
            examined1 = dict()
            examined2 = dict()
            for index, row in examine1.iterrows():
                if str(row["检验结果"]) == 'nan':
                    continue
                if row["体检号"] in namelist:
                    if row["体检号"] not in examined1:
                        examined1[row["体检号"]] = set()
                    examined1[row["体检号"]].add(row["检验项目"])

            for index, row in examine2.iterrows():
                if str(row["检验结果"]) == 'nan':
                    continue
                if row["体检号"] in namelist:
                    if row["体检号"] not in examined2:
                        examined2[row["体检号"]] = set()
                    examined2[row["体检号"]].add(row["检验项目"])

            examined = dict()
            for (k, v) in examined1.items():
                if k in examined2:
                    set1 = v
                    set2 = examined2[k]
                    s = set1.intersection(set2)
                    for v in s:
                        if v not in examined:
                            examined[v] = set()
                        examined[v].add(k)

            if save:
                np.save(save_path, np.array(list(examined.keys())))
                for i,v in enumerate(examined.values()):
                    np.save(os.path.join(self.save_path, self.save_examine_list_values).replace("%d", str(i)), np.array(list(v)))

        return examined

    def filter_names_and_objects(self, examined_list, threshold=2000):
        namelist = False
        objects = list()
        for (k, v) in examined_list.items():
            if len(v) >= threshold:
                if namelist == False:
                    namelist = v
                else:
                    namelist = namelist.intersection(v)
                objects.append(k)
        return list(namelist), objects

    def load_data(self, namelist, objects):
        basic1 = pd.read_csv(os.path.join(self.data_path, self.basic1), encoding='gbk')
        basic2 = pd.read_csv(os.path.join(self.data_path, self.basic2), encoding='gbk')
        basic1 = basic1[basic1["体检号"].isin(namelist)].drop_duplicates(['体检号'])[self.basic_list]
        basic2 = basic2[basic2["体检号"].isin(namelist)].drop_duplicates(['体检号'])[self.basic_list]
        examine1 = pd.read_csv(os.path.join(self.data_path, self.examine1), encoding='gbk')
        examine2 = pd.read_csv(os.path.join(self.data_path, self.examine2), encoding='gbk')

        examine1 = examine1[examine1["体检号"].isin(namelist)]
        examine1 = examine1[examine1["检验项目"].isin(objects)]
        examine1 = examine1.drop_duplicates(["体检号", "检验项目"])[["体检号", "检验项目", "检验结果"]]
        examine_result1 = pd.DataFrame(examine1).pivot(index="体检号", columns="检验项目", values="检验结果")
        all1 = basic1.set_index('体检号').join(examine_result1)

        examine2 = examine2[examine2["体检号"].isin(namelist)]
        examine2 = examine2[examine2["检验项目"].isin(objects)]
        examine2 = examine2.drop_duplicates(["体检号", "检验项目"])[["体检号", "检验项目", "检验结果"]]
        examine_result2 = pd.DataFrame(examine2).pivot(index="体检号", columns="检验项目", values="检验结果")
        all2 = basic2.set_index('体检号').join(examine_result2)

        #载入诊断信息
        diagnosis1 = pd.read_csv(os.path.join(self.data_path, self.diagnosis1), encoding='gbk')
        diagnosis2 = pd.read_csv(os.path.join(self.data_path, self.diagnosis2), encoding='gbk')
        diagnosis1 = diagnosis1[diagnosis1["体检号"].isin(namelist)]
        diagnosis2 = diagnosis2[diagnosis2["体检号"].isin(namelist)]

        # key_words = ["高血压", "糖尿病", "脂肪肝"]
        # columns = []
        # for k in key_words:
        #     columns.append("诊断_" + k)
        #
        # diagnosis = pd.DataFrame(index=all1.index, columns=columns, dtype='Float32')
        # diagnosis = diagnosis.fillna(0.)
        # for index, row in diagnosis1.iterrows():
        #     if not is_nan(row["诊断"]):
        #         for k in key_words:
        #             if row["诊断"].find(k) >= 0:
        #                 diagnosis.loc[row["体检号"], "诊断_" + k] = 1.
        #
        # all1 = all1.join(diagnosis)
        #
        # diagnosis = pd.DataFrame(index=all2.index, columns=columns)
        # diagnosis = diagnosis.fillna(0.)
        # for index, row in diagnosis2.iterrows():
        #     if not is_nan(row["诊断"]):
        #         for k in key_words:
        #             if row["诊断"].find(k) >= 0:
        #                 diagnosis.loc[row["体检号"], "诊断_" + k] = 1.
        #
        # all2 = all2.join(diagnosis)

        return all1, all2

    def data_fix(self, data):
        key_words = ["高血压", "高血脂", "糖尿病", "脂肪肝"]

        columns = []
        for k in key_words:
            columns.append("病史_"+k)
        history = []

        count = 0
        for index, row in data.iterrows():
            value = []
            if not is_nan(row["内科病史"]):
                for k in key_words:
                    if row["内科病史"].find(k) >= 0:
                        value.append(1.)
                    else:
                        value.append(0.)
                value = np.array(value)
            else:
                value = np.zeros(len(key_words))
            history.append(value)

        history = pd.DataFrame(history, columns=columns, index=data.index.values)
        data=pd.concat((data, history),axis=1).drop(["内科病史"], axis=1)

        def get_time(a):
            if is_nan(a):
                return a
            strs = a.split(" ")
            strs = strs[0].split("/")
            return int(strs[0])

        data["出生日期"] = data.apply(lambda row: get_time(row["出生日期"]), axis=1)
        def get_num(str, delem="/", pos=0):
            ret = float('nan')
            if not is_nan(str):
                strs = str.split(delem)
                if len(strs) > pos:
                    ret = float(strs[pos])
            return ret
        data["血压（高）"] = data.apply(lambda row: get_num(row["血压(mmHg)"], pos=0), axis=1)
        data["血压（低）"] = data.apply(lambda row: get_num(row["血压(mmHg)"], pos=1), axis=1)
        del data["血压(mmHg)"]

        def default_value(a, default="未见异常"):
            if is_nan(a):
                return float('nan')
            return 0 if a == default else 1

        for k in ["胸廓","呼吸音","甲状腺","乳腺","眼底(右)","眼底(左)","鼻"]:
            data[k] = data.apply(lambda row: default_value(row[k]), axis=1)

        data.rename(columns={'红细胞计数.':'红细胞计数', '白细胞计数.':'白细胞计数'}, inplace = True)
        return data


    def normalize(self, data):
        for k in self.need_normalize:
            counts = pd.value_counts(data[[k]].values.ravel())
            normalized_values = counts.keys().tolist()

    def gen_label(self, data1, data2, column, max, better_ratio=0.2, label="标记"):
        combined = data1[[column]].join(data2[[column]], lsuffix="1", rsuffix="2")
        if better_ratio > 1:
            better_ratio = 0.99
        def not_better(a, b, max):
            if float(b) > max and float(a) < max:
                return 1
            if float(b) > max and float(a) > max and float(b)/float(a) > 1 - better_ratio:
                return 1
            return 0
        combined["selected"] = combined.apply(lambda row: not_better(row[column+"1"], row[column+"2"], max), axis=1)
        return combined[["selected"]].rename(columns={"selected": label})

    def load_X(self):
        namelist = self.load_namelist()
        examined_list = self.load_examined_list(namelist)
        namelist, examined_objects = self.filter_names_and_objects(examined_list)
        all1, all2 = self.load_data(namelist, examined_objects)
        all1 = self.data_fix(all1)
        all2 = self.data_fix(all2)
        return all1, all2

if __name__ == '__main__':
    data = Data("D:\\Lab\\PE\\data\\csv", "D:\\Lab\\PE\\data\\processed")
    namelist = data.load_namelist()
    examined_list = data.load_examined_list(namelist, load_from_save=True, save=False)
    for (k,v) in examined_list.items():
        print(k, ":", len(v))
    namelist, examined_objects = data.filter_names_and_objects(examined_list)
    print(examined_objects)
    all1, all2 = data.load_data(namelist, examined_objects)
    data.data_fix(all1)
    data.data_fix(all2)
    print(all1.keys())
    print(all2.keys())
    # data.gen_label(all1, all2, "尿酸", 420)
