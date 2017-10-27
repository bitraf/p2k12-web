<html>
<head>
<style>
.grid-table { border-collapse: collapse; margin: 15px auto; }
.grid-table th,
.grid-table td { text-align: left; vertical-align: top; border: 1px solid #aaa; padding: 5px 10px; }
th { background: #eee; }
.n { text-align: right !important; }
.ne { border-bottom: none !important; }
.calendar tr:first-child th { min-width: 100px; }
.calendar td,
.calendar th { text-align: center; vertical-align: middle; font-weight: bold; }
</style>
</head>
<body>
<h2>Tusen takk for at du er medlem av Bitraf, {$name}</h2>

<p>Vi har belastet kredittkortet ditt med {$amount} kr for medlemskap i perioden {$start_date} til {$end_date}.</p>

<p>Medlemsavgiften blir brukt til å betale husleie, vedlikeholde maskiner og kjøpe inn nytt utstyr til medlemmene.</p>

<p>Hvis du vil endre medlemskapet ditt kan du gjøre det <a href='https://p2k12.bitraf.no/mystripe.php?id={$accountId}&signature={$hash}'>her</a>.</p>

<p>Vi anbefaler alle å lese gjennom <a href='https://bitraf.no/wiki/Hvordan_Bitraf_fungerer'>denne siden</a> for å lære om hvordan Bitraf fungerer. Som betalende medlem kan du blant annet få tilgang til å låse opp hoveddøra til Bitraf ved hjelp av mobiltelefonen din. Les mer om dør-passord og medlemsfordeler på linken over.</p>

<h2>Kommende arrangementer</h2>

<table class='grid-table'>
{$events}
</table>

  <p>Du finner arrangementer og mer informasjon på <a href='http://www.meetup.com/bitraf/'>vår side på meetup.com</a>. 
Meetup-siden er der alle arrangementene på Bitraf blir annonsert, så vi anbefaler folk å bruke tjenesten til å holde seg oppdatert.</p>
</body>
</html>
