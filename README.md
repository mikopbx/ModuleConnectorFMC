## MikoPBX Module: FMC Device Connection

Read this in other languages: [English](README.md), [Русский](readme.ru.md)

### What it does
Connect FMC SIM cards to MikoPBX by emulating a SIP phone for each employee. Works with the MCN FMC SIM provider. The PBX registers on the MCN trunk and also "registers" SIMs on employees' SIP accounts, enabling full call control and recording on the PBX.

- Paid module. Provider supported: MCN (FMC SIM).

Reference: [MikoPBX docs — FMC devices (RU)](https://docs.mikopbx.com/mikopbx/modules/miko/podklyuchenie-fms-ustroistv)

### Benefits
- Use GSM for calls; employees stay reachable even without internet
- Record all conversations on the PBX
- CRM integrations remain available
- Full PBX control (IVR, routing, policies) for FMC calls

### How it works
- SIM "registers" on an employee SIP account (e.g., extension `201`).
- Outgoing from SIM: PBX sees a call from internal `201`.
- Incoming to extension `201`: call is delivered to the employee’s SIM.

### Prerequisites
- MikoPBX up and running
- MCN FMC SIM trunk credentials
- Employee mobile numbers stored in user profiles in international format `+7...`

### Setup
1) In each employee card, set the mobile number with `+7` and on the Routing tab disable call forwarding to mobile.
2) Open module settings "FMC Device Connection":
   - Outgoing calls: for Login and Password, click Update.
   - Incoming calls: enter MCN trunk parameters.
   - Employees: select all employees using FMC SIMs.
   - FMC Provider: select MCN.
3) Save. The PBX will register on the MCN trunk and on internal accounts for selected employees.

### Customization (optional)
By default, outgoing calls from SIM follow standard PBX routes. If you want the public caller ID to be the SIM number, add to the end of `extensions.conf` in Customization:

```conf
[outgoing-custom]
exten => _.X!,1,Set(FMC_ID=SIP-FMC-XXXXXX)
    ; Use SIM caller ID only for extensions listed in SIP-FMC-ENABLE-OUT
    same => n,ExecIf($["${DIALPLAN_EXISTS(SIP-FMC-ENABLE-OUT,${CALLERID(num)},1)}" != "1"]?return)
    ; Limit to calls coming from SIM user agent if needed
    ;same => n,ExecIf($["${PJSIP_HEADER(read,User-Agent)}" != "miko-b24-fmc" ]?return)
    same => n,GosubIf($["${DIALPLAN_EXISTS(all-outgoing-${FMC_ID}-custom,${EXTEN},1)}" == "1"]?all-outgoing-${FMC_ID}-custom,${EXTEN},1)
    same => n,return

[SIP-FMC-ENABLE-OUT]
exten => 201,1,NoOp(--- Out call ---)
exten => 202,1,NoOp(--- Out call ---)
```

- `SIP-FMC-XXXXXX` is the login shown in the module under "Outgoing calls from FMC".

### MCN trunk specifics
MCN trunks are typically limited to 1 simultaneous call by default. Increase this limit to match your scenarios. Examples:

- Incoming routed to SIM:
  - 1 line: customer ➜ PBX
  - 1 line: PBX ➜ SIM

- Outgoing from SIM via another provider:
  - 1 line: SIM ➜ PBX
  - 1 line: PBX ➜ external provider ➜ customer

- Outgoing from SIM where customer sees the SIM number:
  - 1 line: SIM ➜ PBX
  - 1 line: PBX ➜ MCN trunk ➜ customer

- Incoming to a public number fan-out to 3 MCN SIMs:
  - 1 line: customer ➜ PBX
  - 3 lines: PBX ➜ three SIM numbers

### Help
- Developer chat: [@mikopbx_dev](https://t.me/joinchat/AAPn5xSqZIpQnNnCAa3bBw)

Source: [MikoPBX docs — FMC devices (RU)](https://docs.mikopbx.com/mikopbx/modules/miko/podklyuchenie-fms-ustroistv)
