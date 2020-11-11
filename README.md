# board-game-nothanks
桌游 No Thanks 的网页版代码，HTML+CSS+JavaScript+PHP+MySQL简单实现

## 部署流程
1. 注意先创建数据库 `CREATE USER 'nothanks'@'%' IDENTIFIED BY '318NoThanks';` 和授权 `GRANT ALL ON *.* TO 'nothanks'@'%';`
2. 将 index.html 和 nothanks.php 文件放到 Apache 目录下即可通过网页开始游戏
3. [Demo](http://81.68.139.159:31880/)

## 游戏规则
1. 每个玩家在自己的回合都有两个选择：接收卡牌或者传递卡牌
2. 接收卡牌则获得卡牌上的筹码，传递卡牌则要将自己的一枚筹码放到卡牌上
3. 接收的所有卡牌的点数和减去玩家剩余的筹码数，即其最终得分
4. 若同时拥有多张点数连续的卡牌，只计算最小的点数
5. 分数最低的玩家取得胜利
6. 卡牌点数为3-35，为保证游戏随机性，随机弃用9张卡牌
7.玩家初始筹码数为11
