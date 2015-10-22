# vycetky-doctrine

Základní informace o systému naleznete <a href="https://github.com/blitzik/vycetky-doctrine/wiki/U%C5%BEivatelsk%C3%A1-%C4%8D%C3%A1st">v github WIKI</a>.

<h2>Instalace</h2>
(PHP 5.6+, MySQL 5.6+)

- stáhnout aplikaci z gitu
- instalace Composer (zároveň stáhnout závislosti)
- vytvoření prázdné databáze pro aplikaci (pracuje pouze pod MySQL)
- v config.local.neon vyplnit údaje k databázi a v parameters.neon nastavit defaultní hodnoty, které budou uvedeny při vytváření nové položky výčetky
  (Vyplnit taky ostatní položky, hlavně pak emaily (mainEmal a emails), aby
   měla kam aplikace odesílat zálohy databáze a v notifikačních emailech byl email
   aplikace, kam budou moci uživatelé zasílat třeba dotazy)
- vygenerovat schéma databáze podle doctrine entit
- instalace Bower [kořenový adresář => bower install --save]
  (je třeba předtím mít nainstalován NPM, poté nainstalovat závislosti deklarované v bower.json)
- instalace Grunt
- nainstalovat závislosti pro Grunt (soubor package.json) příkazem [npm install]
- spustit Grunt (příkaz [grunt] v kořenovém adresáři)

V tuhle chvíli by měla být aplikace ready.

Lze změnit jquery ui theme nahráním vhodného css do složky assets/css/jquery_ui_theme/
a poté v Gruntfile.js v sekci concat:jqueryuiCss pozměnit jaký soubor se má načíst.
Nakonec spustit Grunt, aby to dal všechno dohromady.

<h2>Vytvoření účtu</h2>

V databázi v tabulce <b>invitation</b> vytvořit pozvánku a pomocí této pozvánky
vytvořit účet v registrační části aplikace.

<h2>Ostatní</h2>

Pro automatickou zálohu databáze lze vytvořit úlohu pro CRON, která bude
volat adresu www.vase-domena.cz/backup/database-backup?do=databaseBackup-backup&databaseBackup-pass=<b>HESLO_UVEDENE_V_CONFIGU</b>
(na konci adresy se zadává heslo, které se nastaví v config.local.neon)

zálohuje se vždy jen jednou za den, pokud již záloha existuje, tak se další nevytváří.
Je-li potřeba stejně vytvořit zálohu, uživatel v roli administrátora má možnost
vytvořit zálohu kliknutím na tlačítko zálohy ve svém účtu.

Pokud je třeba vytvářet zálohu vícekrát denně, bude potřeba přepsat script.