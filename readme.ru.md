
### Чтобы вызов прошел с номера sim карты, а не по основным маршрутам
```
[outgoing-custom];
exten => _.X!,1,NoOp(-- ${PJSIP_HEADER(read,User-Agent)} --)
    same => n,ExecIf($["${DIALPLAN_EXISTS(SIP-FMC-ENABLE-OUT,${CALLERID(num)},1)}" != "1"]?return)
    ;same => n,ExecIf($["${PJSIP_HEADER(read,User-Agent)}" != "miko-b24-fmc" ]?return)    
    same => n,GosubIf($["${DIALPLAN_EXISTS(all-outgoing-SIP-FMC-Z5HGGORU-custom,${EXTEN},1)}" == "1"]?all-outgoing-SIP-FMC-Z5HGGORU-custom,${EXTEN},1)
    same => n,return
    
[SIP-FMC-ENABLE-OUT]
exten => 201,1,NoOp(--- Out call ---)
exten => 906,1,NoOp(--- Out call ---)
exten => 908,1,NoOp(--- Out call ---)
exten => 914,1,NoOp(--- Out call ---)
exten => 903,1,NoOp(--- Out call ---)


```
 - "SIP-FMC-6AC9FJ0E" - идентификатор 