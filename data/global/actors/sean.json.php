{
    "@context":
    [
        "https://www.w3.org/ns/activitystreams",
        "https://w3id.org/security/v1"
    ],
    "id": "http://<?=$domain;?>/ap/actor/sean",
    "type": "Person",
    "preferredUsername": "sean",
    "outbox": "http://<?=$domain;?>/ap/actor/sean/outbox",
    "inbox": "http://<?=$domain;?>/ap/actor/sean/inbox",
    "endpoints": {"sharedInbox": "http://<?=$domain;?>/ap/inbox"}
    "publicKey":
    {
        "id": "http://<?=$domain;?>/ap/actor/sean#main-key",
        "owner": "http://<?=$domain;?>/ap/actor/sean",
        "publicKeyPem": "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5A/KIZrjEP3He7ucgAWM\n8SC9s7lGIcyggJiTbwWyrtuknPrnSKT898ckVhsm2Mxi5BfSzHFvQxo852sGmPEH\nCtN2pBrE70GiFipRTvjQf+ugRpbSgol3m12OH02M/75jTG+eAiILKUQIPBvuvWdZ\nRt/+O5BhoQNx+pEFZyijrLz/V3pRdj7Cof6QGySNwkIE1DYukhblyoeEXIDKZcxi\nFtfnHSy/Mjo8CZzqNPFyL3CFDOV83+KenwV2YKScrnU1iX415IH6ATP6UzfOph9I\nl2ZQEhV5JAMPWEnwLdJBIk1qUEwzYTz10YZtHVPTteZe5vGESOXpM3xwalJqC/E5\n4wIDAQAB\n-----END PUBLIC KEY-----\n"
    }
}
