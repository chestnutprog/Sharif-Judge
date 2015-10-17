#!/usr/bin/env python
#-*coding:utf-8*-
import lorun
import shlex
import os
import sys
import commands
import time 
import shutil
import re

RESULT_STR = [
    '通过Accepted',
    '通过Accepted',
    '超时Time Limit Exceeded',
    '内存超限Memory Limit Exceeded',
    '错误答案Wrong Answer',
    '运行时错误Runtime Error',
    '输出超限Output Limit Exceeded',
    'Compile Error',
    'System Error',
    '未找到输出'
]
runcode = {
    8:'整数被零除',
    11:'访问无效内存',
    17:'子进程异常结束'
}
infs = os.listdir(sys.argv[3]+'/in')
outfs = os.listdir(sys.argv[3]+'/out')
infs.sort(key=lambda x:int(re.findall(r"\d+\.?\d*",os.path.splitext(x)[0])[-1]))
outfs.sort(key=lambda x:int(re.findall(r"\d+\.?\d*",os.path.splitext(x)[0])[-1]))
acs = 0
for i in range(len(infs)):
 ins = sys.argv[3]+'/in/'+ infs[i]
 if (i<len(outfs)): 
  outs = sys.argv[3]+'/out/'+ outfs[i]
 else:
  outs = 'none'
 outpath = 'output.txt'
 shutil.copy(ins, 'input.txt')
 ftemp = open('out', 'w')
 ferr = open('errs', 'w')
 runcfg = {
  'args':shlex.split(sys.argv[4]),
  'fd_out':ftemp.fileno(),
  'fd_err':ferr.fileno(),
  'timelimit':int(sys.argv[1]), #in MS
  'memorylimit':int(sys.argv[2]), #in KB
 }
 runcfg['trace'] = True
 runcfg['calls'] = [0,1,2,3,4,5,6,8,9,10,11,12,13,14,16,20,21,24,41,42,56,63,72,78,79,89,97,102,104,107,108,137,158,202,218,231,257,273,292,293,201] # system calls that could be used by testing programs
 runcfg['files'] = {'/etc/ld.so.cache': 0x80000, 
 'input.txt':0,
 'output.txt':0x241,
 '/proc/meminfo':0x80000,
 '/etc/nsswitch.conf':0x80000,
 '/etc/passwd':0x80000,
 shlex.split(sys.argv[4])[-1]:0,}
 rst = lorun.run(runcfg)
 ftemp.close() 
 ferr.close()
 if not os.path.exists(outpath):
   if rst['result'] == 0:
     rst['result']=9
 if rst['result'] == 0:
  if os.path.exists("shj_tester"):
   rst['result'] = 8
   (status,papapa) = commands.getstatusoutput('./shj_tester '+ ins + ' '+ outs +' '+outpath)
   if(status == 0):
     rst['result'] = 0
   if(status == 256):
     rst['result'] = 4
  elif os.path.exists(outs):
   fout = open(outs)
   ftemp = open(outpath)
   crst = lorun.check(fout.fileno(), ftemp.fileno())
   fout.close()
   ftemp.close()
#  os.remove('out')
   if crst != 0:
    rst['result']=crst     
 rst['result']=RESULT_STR[rst['result']]
 print ('<span class=\"shj_b\">Test %d</span>' % (i+1) )
 if 're_signum' in rst.keys():
  if rst['re_signum'] in runcode.keys():
   print '<span class="shj_o">',runcode[rst['re_signum']],'</span>',
 if 're_file_flag' in rst.keys():
  print '<span class="shj_o">','尝试读写文件',rst['re_file'],'</span>',
 if rst['result'] == '通过Accepted':
  print '<span class="shj_g">',
  acs+=1
 else:
  print '<span class="shj_r">',
 print ('%-22s %-20s %-22s</span> ' % ('使用内存 '+str(rst['memorymax'])+'KB','总执行时间 '+str(rst['timeused']+rst['timesystem'])+'ms','结果 '+str(rst['result'])))
acc = open('acc', 'w')  
acc.write(str(acs*10000/ len(infs)))
acc.close()
