# vycetky-doctrine

Základní informace o systému naleznete <a href="https://github.com/blitzik/vycetky-doctrine/wiki/U%C5%BEivatelsk%C3%A1-%C4%8D%C3%A1st">v github WIKI</a>.

<h2>Instalace</h2>
(PHP 5.6+, MySQL 5.6+)

- stáhnout aplikaci z gitu
- instalace Composer (zároveň stáhnout závislosti)
- vytvoření prázdné databáze pro aplikaci (pracuje pouze pod MySQL)
- v config.local.neon vyplnit údaje k databázi
- vygenerovat schéma databáze podle doctrine entit
- instalace Bower (je třeba předtím mít nainstalován NPM, poté nainstalovat závislosti deklarované v bower.json)
- instalace Grunt ( příkazem grunt v kořenovém adresáři zpracovat CSS, JS, apod.)

V tuhle chvíli by měla být aplikace ready.

<h2>Vytvoření účtu</h2>

V databázi v tabulce <b>invitation</b> vytvořit pozvánku a pomocí této pozvánky
vytvořit účet v registrační části aplikace.