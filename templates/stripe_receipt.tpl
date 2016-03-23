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

<p>Medlemsavgiften blir brukt til å betale husleie og kjøpe inn nytt utstyr til medlemmene.</p>

<p>Hvis du vil endre medlemskapet ditt kan du gjøre det <a href='https://p2k12.bitraf.no/mystripe.php?id={$accountId}&signature={$hash}'>her</a>.</p>

<p>Som betalende medlem kan du få tilgang til å låse opp hoveddøra ved Bitraf ved hjelp av mobiltelefonen din. For å gjøre dette må du sette et passord ved å møte opp i lokalet. Du kan også sette igjen en prosjektboks i lokalet, bruke basiskomponenter og maskiner som fres og laser (kun for medlemmer). Mer informasjon om oss finner du på <a href='http://bitraf.no'>bitraf.no</a>.

<h2>Kommende arrangementer</h2>

<table class='grid-table'>
{$events}
</table>

  <p>Du finner arrangementer og mer informasjon på <a href='http://www.meetup.com/bitraf/'>vår side på meetup.com</a>. 
Meetup-siden er der alle arrangementene på Bitraf blir annonsert, så vi anbefaler folk å bruke tjenesten til å holde seg oppdatert.</p>
</body>
</html>
