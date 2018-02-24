<html>
<head>
<!-- Google Tag Manager, BL's account -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WP3CM2M');</script>
<!-- End Google Tag Manager -->

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
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WP3CM2M"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<h2>Tusen takk for at du er medlem av Bitraf, {$name}</h2>

<p>Vi har belastet kredittkortet ditt med {$amount} kr for medlemskap i perioden {$start_date} til {$end_date}.</p>

<p>Medlemsavgiften blir brukt til å betale husleie, vedlikeholde maskiner og kjøpe inn nytt utstyr til medlemmene.</p>

<p>Hvis du vil endre medlemskapet ditt kan du gjøre det <a href='https://p2k12.bitraf.no/mystripe.php?id={$accountId}&signature={$hash}'>her</a>.</p>

<p>Vi anbefaler alle å lese gjennom <a href='https://bitraf.no/wiki/Hvordan_Bitraf_fungerer'>denne siden</a> for å lære om hvordan Bitraf fungerer. Som betalende medlem kan du blant annet få tilgang til å låse opp hoveddøra til Bitraf ved hjelp av mobiltelefonen din. Les mer om dør-passord og medlemsfordeler på linken over.</p>

<h2>Nye regler for bruk av CNC</h2>
<p>Som mange sikkert har lagt merke til er shopboten kontinuerlig overbooket for tiden, men når vi har målt faktisk bruk ser vi at tiden ikke blir utnyttet særlig bra. Alle er tjent med at fresen faktisk utnyttes effektivt slik at så mange medlemmer som mulig kan få bruke de.</p>

<p> Derfor prøver vi oss på noen nye retningslinjer for å øke effektiviteten på bruken av maskinen. Dette er et forsøk, og det kan godt hende vi må revidere retningslinjene i ukene og månedene fremover. Men fra den 4. desember innfører vi altså følgende:</p>

<ul>
<li> Shopboten kan forhåndsbookes gratis i maksimum 12 timer i løpet av en 4 ukers periode.
<li> For alle bookinger som varer mer enn 4 timer, må grunnen til den lange fresetiden forklares i beskrivelsen i kalenderen.
<li> Bookinger om natten mellom 01.00 og 07.00 regnes ikke med i telling av timer.
<li> Bookinger gjort mindre enn 72 timer i forkant regnes ikke med i telling av timer. Disse bookingene merkes med "KORTTID" i kalenderen.
<li> Kommersielle og betalte bookinger regnes ikke med i telling av timer. Disse bookingene merkes med "BETALT" i kalenderen.
</ul>

<p>
Med disse grunnreglene håper vi å gjøre det litt lettere for alle å få gjennomført de sinnsykt kule prosjektene sine på Bitraf.<p/>

<h2>Kommende arrangementer</h2>

<table class='grid-table'>
{$events}
</table>

  <p>Du finner arrangementer og mer informasjon på <a href='http://www.meetup.com/bitraf/'>vår side på meetup.com</a>. 
Meetup-siden er der alle arrangementene på Bitraf blir annonsert, så vi anbefaler folk å bruke tjenesten til å holde seg oppdatert.</p>
</body>
</html>
