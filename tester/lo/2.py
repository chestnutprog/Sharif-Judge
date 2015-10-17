#!/usr/bin/env python
#-*coding:utf-8*-
import _lorun_ext
import lorun
import os
import sys
fin = open('temp.in')
ftemp = open('temp.out', 'w')
runcfg = {
    'args':['./test'],
    'fd_in':fin.fileno(),
    'fd_out':ftemp.fileno(),
    'timelimit':1000, #in MS
    'memorylimit':20000, #in KB
}
rst = lorun.run(runcfg)
fin.close()
ftemp.close()
print (rst)