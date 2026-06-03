# NetwerkPlugin

![NetwerkPlugin](https://img.shields.io/badge/Q-Home-NetwerkPlugin-blue)
![License](https://img.shields.io/badge/license-Apache%202.0-green)

## 🚀 Wat is NetwerkPlugin?

NetwerkPlugin is een krachtige LoxBerry-plugin voor netwerkdetectie, monitoring en automatisering. De plugin brengt netwerkstatussen direct beschikbaar in je LoxBerry-omgeving en maakt integratie met Loxone eenvoudig.

## ✨ Hoofdpunten

- Netwerkapparaten automatisch detecteren en monitoren
- Handige webinterface voor eenvoudige configuratie
- Integratie met Loxone en LoxBerry workflows
- Configureerbare scans, logging en notificaties
- Ondersteuning voor automatische plugin-updates

## 📌 Belangrijkste functies

| Feature      | Beschrijving                                           |
| ------------ | ------------------------------------------------------ |
| Detectie     | Vind apparaten in je netwerk zonder handmatig scannen  |
| Monitoring   | Houd beschikbaarheid en connectiviteit in de gaten     |
| DNS Server   | Biedt een lokale DNS-resolver voor gedetecteerde hosts |
| Integratie   | Koppel netwerkstatussen aan Loxone automatiseringen    |
| Logging      | Verzamel diagnostische data voor probleemoplossing     |
| Configuratie | Stel bereik, interval en meldingen flexibel in         |

## ⚙️ Vereisten

### Systeem

- LoxBerry (laatste stabiele versie aanbevolen)
- Linux-gebaseerd LoxBerry-systeem
- Netwerktoegang tot de te beheren apparaten

### Ontwikkeling

- Git
- Node.js
- npm

## 📥 Installatie

### Installatie via LoxBerry

1. Download de nieuwste release van GitHub
2. Open de LoxBerry webinterface
3. Ga naar **Plugin Management**
4. Selecteer **Plugin installeren**
5. Upload het pluginpakket
6. Volg de installatiestappen

### Handmatige installatie

```bash
git clone https://github.com/Q-Home/NetwerkPlugin.git
```

Verpak de plugin daarna volgens de LoxBerry-richtlijnen en installeer via de Plugin Manager.

## ⚙️ Configuratie

Na installatie vind je de plugin via:

```text
LoxBerry → Plugins → NetwerkPlugin
```

Configureer hier onder andere:

- Netwerkbereiken
- Scanintervallen
- Loggingniveau
- Integratie-instellingen
- Meldingen en statusupdates
- DNS-servernaamgeving voor lokaal netwerk

## 🌐 DNS Server

Met de nieuwe DNS-serverfunctie kun je gedetecteerde devices lokaal benaderen via hostname.

### Wat doet het

- Bouwt een eenvoudige A-record resolver op basis van gescande apparaten
- Voegt handmatige host mappings toe via de webinterface
- Servert DNS-query's op een gekozen poort (bijvoorbeeld 5353)

### Hoe te gebruiken

1. Open de plugin in de LoxBerry webinterface.
2. Ga naar **DNS Server**.
3. Stel het domein in, bijvoorbeeld `local`.
4. Kies een poort, bijvoorbeeld `5353` als je geen root-toegang hebt.
5. Voeg handmatige mappings toe als je vaste namen wilt gebruiken.
6. Start de DNS-server op de LoxBerry met:

```bash
loxberry php /opt/loxberry/bin/plugins/network_plugin/dns_server.php --port=5353
```

> Gebruik `--port=53` alleen als je het script als root kunt draaien.

### Voorwaarden

- `php` moet beschikbaar zijn op het systeem
- `php` moet de `sockets`-extensie hebben
- `nmap` en netwerkscanning moeten al werken voor hostdetectie

### Resultaat

- Het systeem biedt nu een lokale DNS-resolver voor hosts die door de plugin zijn ontdekt
- Je kunt vanaf andere apparaten in hetzelfde netwerk query's doen naar `host.local` of `host.<domein>`

## 📁 Projectstructuur

```text
NetwerkPlugin/
├── bin/             # plugin scripts
├── config/          # configuratiebestanden
├── cron/            # geplande taken
├── data/            # runtime data
├── icons/           # iconen voor de plugin
├── sudoers/         # sudo configuraties
├── templates/       # web templates
├── webfrontend/     # gebruikersinterface
├── plugin.cfg
├── package.json
└── README.md
```

## 🛠️ Ontwikkelen

### Repository clonen

```bash
git clone https://github.com/Q-Home/NetwerkPlugin.git
cd NetwerkPlugin
```

### Dependencies installeren

```bash
npm install
```

### Lokaal testen

Ontwikkelingen kunnen lokaal worden gemaakt en getest in een LoxBerry ontwikkelomgeving.

## 🚚 Releaseproces

Deze repository bevat tools voor geautomatiseerde release-workflows.

### Nieuwe release

```bash
npm install
npm run release
```

### Prerelease

```bash
npm run prerelease
```

Tijdens releases worden automatisch:

- versienummers bijgewerkt
- `plugin.cfg` aangepast
- Git-tags aangemaakt
- changelog gegenereerd
- GitHub Release voorbereid

## 🧾 Logging

Logbestanden zijn beschikbaar via de standaard LoxBerry logbeheerinterface:

```text
LoxBerry → Log Management
```

## 🤝 Bijdragen

Bijdragen zijn welkom!

1. Fork de repository
2. Maak een feature branch

```bash
git checkout -b feature/mijn-feature
```

3. Commit je wijzigingen

```bash
git commit -m "Nieuwe functionaliteit toegevoegd"
```

4. Push je branch

```bash
git push origin feature/mijn-feature
```

5. Maak een Pull Request

## 📫 Ondersteuning

Voor bugs, verzoeken of vragen:

- Open een issue op GitHub
- Neem contact op met Q-Home

## 📄 Licentie

NetwerkPlugin is gelicentieerd onder Apache License 2.0.
Zie `LICENSE` voor de volledige licentietekst.

Ontwikkeld door **Q-Home**
