# Lingo

Lingo is a real-time multiplayer word game built with Laravel, Vue, and Inertia.


## Reflectie
 
### Multiplayer soort
 
Ik ben gegaan voor een multiplayer versus app waarin 2 spelers tegelijkertijd tegen elkaar spelen. Het heeft 3 rondes en per ronde een tijdslimiet van 5 minuten. Als een speler het woord raadt dan gaat het spel door naar de volgende ronde en heeft de andere speler niet meer de kans om het woord te raden.
 
Ik ben voor deze versie van multiplayer gegaan omdat dit het meeste aanvoelt als een spel, en haalbaar was in 2 uur. Het eindresultaat voelt polished, met een nette huisstijl, leuke animaties voor tegels, en mooie error handling.
 
### Werkwijze
 
Ik heb gewerkt in korte prompts, ieder in een eigen sessie. Ik heb een instructiebestand specifiek voor deze app toegevoegd om de regels duidelijk te houden door de verschillende sessies heen. Hier staat bijvoorbeeld in dat het geheime woord nooit naar de client gaat voor een ronde eindigt, en waar game logic komt te staan. Ook heb ik Laravel Boost gebruikt. Prompts staan in /prompts
 
### Duur
 
Ik ben er net iets onder de 2 uur mee bezig geweest, los van het exporteren van de chat history en het schrijven van de readme.
 
### Controle
 
De hele app is test driven ontwikkeld en de meeste cases zijn gedekt, waaronder dat een game niet kan worden gestart als een van de spelers is geforfeit. Ik heb ook na iedere update snel de code door zitten lezen om overduidelijke fouten te vinden. Daarnaast had ik het spel in 2 browsers open om het in de realiteit te testen.
 
### Bijsturen
 
Ik heb niet veel hoeven bijsturen. Alleen bij het opbouwen van de matchlogica had de agent zijn eigen regels bedacht. In plaats van een race met 1 iemand die het woord raadt kreeg iedereen de kans om het woord te raden en ging de ronde pas als iedereen klaar was naar de volgende ronde. De volgende keer zou ik dit beter kunnen doen door explicieter te zijn in de regels die ik wil. Een plan van tevoren laten maken op basis van mijn prompt zou ook erg helpen.
 
Ook kwamen er hier wat duidelijke bugs naar boven. Bijvoorbeeld: als een speler wegging, kreeg de overgebleven speler de optie om de game opnieuw te starten. Dit startte een nieuwe match tegen een speler die al weg was. Ik heb de agent dit laten oplossen door geen games te starten in een kamer met een speler die is weggegaan, en een test voor deze case laten schrijven.
 
Ik stopte beide problemen in 1 correctieprompt. Beter was geweest ze apart te doen, zodat elke wijziging eenvoudig te beoordelen en te committen is.
 
Correctieprompt (licht gecorrigeerd op typfouten):
 
```
Some issues:
1. When a player leaves and you win by forfeit, the button to restart shows up and starts a new round against a player who has already left. Fix this and write a test.
2. Game rules. Wanted rules: When a player guesses the word, the round ends. Points are awarded to the round winner. The word is shown to all players. We go to the next round.
Current implementation: one player guesses the word. The round continues. The other player guesses the word. All players get the same points (if they took the same number of guesses).
```
 
### Verbeterpunten
 
Ik beheerde zelf de git repo via mijn git client. Soms prompte ik de agent opnieuw voordat ik de vorige wijziging had gecommit. Dit is hier goed gegaan, maar had voor wat tijdsverlies kunnen zorgen. De volgende keer laat ik de agent per prompt op een featurebranch committen, zodat elke prompt aan een commit gekoppeld is.
 
### Met meer tijd
 
Als ik meer tijd zou hebben, zou ik een rate limiter toevoegen op de guess-route. Dit kwam als resultaat van mijn code review-prompt, maar ik had geen tijd om dit nog door te voeren. Ook zou ik een singleplayer-modus toevoegen. Daarnaast zou ik regelinstellingen toevoegen, zodat een speler kan kiezen hoeveel rondes er zijn, hoe lang de woorden zijn en hoeveel tijd er is om een woord te raden.


## Run locally

### Prerequisites

- PHP 8.3 or newer, with the SQLite/PDO SQLite extension enabled
- Composer
- Node.js and npm
- Git

Check that the tools are available in your terminal:

```shell
php -v
composer --version
node --version
npm --version
```

### 1. Get the code

Clone the repository and move into its directory:

```shell
git clone <repository-url> lingo
cd lingo
```

Replace `<repository-url>` with the URL for this repository.

### 2. Create the local SQLite database file

The app uses SQLite by default. Create its database file before running setup.

**macOS** (Terminal):

```shell
touch database/database.sqlite
```

**Windows** (PowerShell):

```powershell
New-Item -ItemType File -Force database\database.sqlite
```

### 3. Install and configure

From the project directory, run:

```shell
composer run setup
```

This installs PHP and JavaScript dependencies, creates `.env` from `.env.example`,
generates the application key, runs database migrations, and builds the frontend.
The default configuration uses the local SQLite database and Reverb for
real-time updates; no separate database server is needed.

### 4. Start the app

Open two terminals in the project directory.

In the first terminal, start Reverb, the queue worker, and the Vite development
server:

```shell
composer run dev
```

In the second terminal, start Laravel's local web server:

```shell
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in your browser. Keep both
terminals running while using the app. Press `Ctrl+C` in each terminal to stop
the processes.
