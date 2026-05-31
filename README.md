# NetwerkPlugin

NetwerkPlugin is een LoxBerry-plugin ontwikkeld door Q-Home voor het beheren, monitoren en automatiseren van netwerkgerelateerde functionaliteiten binnen een LoxBerry-omgeving.

## Overzicht

Deze plugin biedt een eenvoudige manier om netwerkapparaten te detecteren, monitoren en integreren binnen LoxBerry en Loxone automatiseringen. Het doel van de plugin is om netwerkstatussen beschikbaar te maken voor automatiseringsscenario's en netwerkbeheer te vereenvoudigen.

## Functies

* Detecteren van netwerkapparaten
* Monitoren van netwerkstatussen
* Integratie met Loxone via LoxBerry
* Gebruiksvriendelijke webinterface
* Automatische updates via LoxBerry Plugin Management
* Logging en diagnostische informatie
* Configureerbare netwerkparameters

## Vereisten

### Software

* LoxBerry (laatste stabiele versie aanbevolen)
* Linux-gebaseerd LoxBerry systeem
* Netwerktoegang tot de te beheren apparaten

### Ontwikkeling

* Git
* Node.js
* npm

## Installatie

### Installatie via LoxBerry

1. Download de laatste release via GitHub.
2. Open de LoxBerry webinterface.
3. Ga naar **Plugin Management**.
4. Kies **Plugin installeren**.
5. Upload het pluginpakket.
6. Voltooi de installatieprocedure.

### Handmatige installatie

```bash
git clone https://github.com/Q-Home/NetwerkPlugin.git
```

Verpak de plugin vervolgens volgens de LoxBerry richtlijnen en installeer deze via de Plugin Manager.

## Configuratie

Na installatie is de plugin beschikbaar via:

```text
LoxBerry → Plugins → NetwerkPlugin
```

Hier kunnen onder andere de volgende instellingen worden geconfigureerd:

* Netwerkbereiken
* Scanintervallen
* Loggingniveau
* Integratie-instellingen
* Meldingen en statusupdates

## Projectstructuur

```text
NetwerkPlugin/
├── bin/
│   └── plugin scripts
│
├── config/
│   └── configuratiebestanden
│
├── cron/
│   └── geplande taken
│
├── data/
│   └── runtime data
│
├── icons/
│   └── plugin iconen
│
├── sudoers/
│   └── sudo configuraties
│
├── templates/
│   └── web templates
│
├── webfrontend/
│   └── gebruikersinterface
│
├── plugin.cfg
├── package.json
└── README.md
```

## Ontwikkeling

### Repository clonen

```bash
git clone https://github.com/Q-Home/NetwerkPlugin.git
cd NetwerkPlugin
```

### Dependencies installeren

```bash
npm install
```

### Lokale ontwikkeling

Na het clonen kunnen wijzigingen lokaal worden ontwikkeld en getest binnen een LoxBerry ontwikkelomgeving.

## Releaseproces

Het project bevat tooling voor geautomatiseerde releases.

### Nieuwe release

```bash
npm install
npm run release
```

### Prerelease

```bash
npm run prerelease
```

Tijdens het releaseproces worden automatisch:

* Versienummers bijgewerkt
* `plugin.cfg` aangepast
* Git-tags aangemaakt
* Changelog bijgewerkt
* GitHub Release voorbereid

## Logging

Logbestanden worden opgeslagen volgens de standaard LoxBerry conventies en kunnen worden geraadpleegd via:

```text
LoxBerry → Log Management
```

## Bijdragen

Bijdragen zijn welkom.

### Workflow

1. Fork de repository
2. Maak een feature branch

```bash
git checkout -b feature/mijn-feature
```

3. Commit je wijzigingen

```bash
git commit -m "Nieuwe functionaliteit toegevoegd"
```

4. Push naar je branch

```bash
git push origin feature/mijn-feature
```

5. Maak een Pull Request

## Ondersteuning

Voor bugs, feature requests of vragen:

* Open een issue op GitHub
* Neem contact op met Q-Home

## Repository

GitHub:

https://github.com/Q-Home/NetwerkPlugin

## Licentie

Dit project wordt uitgebracht onder de Apache License 2.0.

Zie het bestand `LICENSE` voor de volledige licentietekst.

---

Ontwikkeld door **Q-Home**
