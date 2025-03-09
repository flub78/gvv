# MySql Queries

Quelques examples de requêtes sur la base de données.

## Sur les vols planeurs

select vpdate, vppilid from volsp;

Tous les jours ou des personnes ont volé entre deux dates

select distinct CONCAT_WS(' ', mnom, mprenom) as nom, vpdate from volsp, membres
where (vpinst  = mlogin or vppilid = mlogin)
and vpdate >= '2011-07-10'
and vpdate <= '2011-07-28'
group by nom, vpdate

## Sur les vols avion

select vadate, vapilid from volsa;

Tous les jours ou des personnes ont volé entre deux dates

select distinct CONCAT_WS(' ', mnom, mprenom) as nom, vadate from volsa, membres
where vapilid  = mlogin
and vadate >= '2011-07-10'
and vadate <= '2011-07-28'
group by nom, vadate

select * from volsa
select vadate, vamacid, sum(vaduree), varem
where vadate >= '2012-01-01'
and vadate <= '2012-12-31'
and vamacid = 'F-JTXF'
and varem=1

select * from volsp
where vpdate >= '2012-01-01'
and vpdate <= '2012-12-31'
where remorqueur = 'F-JTXF'

## Solde des comptes

SELECT 
    c.id,
    c.nom,
    c.codec,
    COALESCE(SUM(CASE WHEN e.compte2 = c.id THEN e.montant ELSE 0 END), 0) AS total_credit,
    COALESCE(SUM(CASE WHEN e.compte1 = c.id THEN e.montant ELSE 0 END), 0) AS total_debit,
    COALESCE(SUM(CASE WHEN e.compte2 = c.id THEN e.montant ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN e.compte1 = c.id THEN e.montant ELSE 0 END), 0) AS solde
FROM 
    comptes c
LEFT JOIN 
    ecritures e ON (e.compte1 = c.id OR e.compte2 = c.id)
WHERE 
    c.id = 37
GROUP BY 
    c.id, c.nom, c.codec;

## Solde des comptes par CODEC

Voici une requête MySQL qui calcule le solde de tous les comptes ayant le même code comptable (codec) :


SELECT 
    c.codec AS code_comptable,
    pc.pdesc AS description_plan_comptable,
    COALESCE(SUM(CASE WHEN e.compte2 = c.id THEN e.montant ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN e.compte1 = c.id THEN e.montant ELSE 0 END), 0) AS solde_total
FROM 
    comptes c
LEFT JOIN 
    ecritures e ON (e.compte1 = c.id OR e.compte2 = c.id)
LEFT JOIN
    planc pc ON c.codec = pc.pcode
WHERE 
    club=1
GROUP BY 
    c.codec, pc.pdesc
ORDER BY
    c.codec;

La même avec une date limite.

SELECT 
    c.codec AS code_comptable,
    pc.pdesc AS description_plan_comptable,
    COALESCE(SUM(CASE WHEN e.compte2 = c.id THEN e.montant ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN e.compte1 = c.id THEN e.montant ELSE 0 END), 0) AS solde_total
FROM 
    comptes c
LEFT JOIN 
    ecritures e ON (e.compte1 = c.id OR e.compte2 = c.id) AND e.date_op <= :date_limite
LEFT JOIN
    planc pc ON c.codec = pc.pcode
GROUP BY 
    c.codec, pc.pdesc
ORDER BY
    c.codec;

La même en active record.

```
    $query = DB::table('comptes AS c')
        ->select(
            'c.codec AS code_comptable',
            'pc.pdesc AS description_plan_comptable',
            DB::raw('COALESCE(SUM(CASE WHEN e.compte2 = c.id THEN e.montant ELSE 0 END), 0) - 
                     COALESCE(SUM(CASE WHEN e.compte1 = c.id THEN e.montant ELSE 0 END), 0) AS solde_total')
        )
        ->leftJoin('ecritures AS e', function($join) {
            $join->on('e.compte1', '=', 'c.id')
                 ->orOn('e.compte2', '=', 'c.id');
        })
        ->leftJoin('planc AS pc', 'c.codec', '=', 'pc.pcode')
        ->groupBy('c.codec', 'pc.pdesc')
        ->orderBy('c.codec');
    
    // Exécution de la requête
    $resultats = $query->get();
```


```
    // Préparation de la requête
    $query = DB::table('comptes AS c')
        ->select(
            'c.codec AS code_comptable',
            'pc.pdesc AS description_plan_comptable',
            DB::raw('COALESCE(SUM(CASE WHEN e.compte2 = c.id THEN e.montant ELSE 0 END), 0) - 
                     COALESCE(SUM(CASE WHEN e.compte1 = c.id THEN e.montant ELSE 0 END), 0) AS solde_total')
        )
        ->leftJoin(DB::raw('ecritures AS e ON ((e.compte1 = c.id OR e.compte2 = c.id) AND e.date_op <= :date_limite)'), 
                  [], null, [':date_limite' => $dateLimite])
        ->leftJoin('planc AS pc', 'c.codec', '=', 'pc.pcode');
    
    // Application des filtres de codec
    if (!empty($codec)) {
        // Filtre pour un codec spécifique
        $query->where('c.codec', '=', $codec);
    } elseif (!empty($codecStart) && !empty($codecEnd)) {
        // Filtre pour une plage de codecs
        $query->where('c.codec', '>=', $codecStart)
              ->where('c.codec', '<=', $codecEnd);
    } elseif (!empty($codecStart)) {
        // Filtre à partir d'un codec de départ
        $query->where('c.codec', '>=', $codecStart);
    } elseif (!empty($codecEnd)) {
        // Filtre jusqu'à un codec de fin
        $query->where('c.codec', '<=', $codecEnd);
    }
    
    // Groupement et tri
    $query->groupBy('c.codec', 'pc.pdesc')
          ->orderBy('c.codec');
    
    // Exécution de la requête
    $resultats = $query->get();
```
================================================================================================

select * from volsp, membres where vppilid=mlogin
select vpdate, vppilid, vpmacid, vpcdeb, vpcfin from volsp, membres where vppilid=mlogin

select vpdate, mprenom, mnom, vpmacid, vpcdeb, vpcfin, vpduree, vpobs, vpdc, vpinst, remorqueur, pilote_remorqueur  
from volsp, membres 
where vppilid=mlogin

--
-- Structure de la vue `vue_credit`
--
CREATE VIEW `vue_credit` AS select `comptes`.`codec` AS `codec2`,`ecritures`.`compte2` AS `compte2`,`comptes`.`nom` AS `nom`,sum(`ecritures`.`montant`) AS `credit` from (`ecritures` join `comptes`) where (`ecritures`.`compte2` = `comptes`.`id`) group by `ecritures`.`compte2` order by `comptes`.`codec`;

--
-- Structure de la vue `vue_debit`
--
CREATE VIEW `vue_debit` AS select `comptes`.`codec` AS `codec1`,`ecritures`.`compte1` AS `compte1`,`comptes`.`nom` AS `nom`,sum(`ecritures`.`montant`) AS `debit` from (`ecritures` join `comptes`) where (`ecritures`.`compte1` = `comptes`.`id`) group by `ecritures`.`compte1` order by `comptes`.`codec`;

====================================================================================================
last query = 
SELECT `mlogin`, `mprenom`, `mnom`, `mtelf`, `mtelm`, `memail`, `m25ans`, `actif`, `ext` 
FROM (`membres`) 
ORDER BY `mnom`, `mprenom` LIMIT 50

last query = 
SELECT `mlogin`, `mprenom`, `mnom`, `madresse`, `cp`, `ville`, `mdaten` 
FROM (`membres`) 
WHERE `actif` = 1 ORDER BY `mnom`, `mprenom`

last query = 
SELECT `mpimmat`, `mpmodele`, `mpnumc`, `mpconstruc`, `mpbiplace`, `mpautonome`, `mptreuil`, `mpprive`, `mmax_facturation`, `actif`, `tarifs1`.`prix` as prix, `tarifs2`.`prix` as prix_forfait 
FROM (`machinesp`, `tarifs` as tarifs1, `tarifs` as tarifs2) 
WHERE `machinesp`.`mprix` = tarifs1.id and machinesp.mprix_forfait = tarifs2.id LIMIT 50

last query = 
SELECT `macmodele`, `macimmat`, `macconstruc`, `macplaces`, `macrem`, `maprive`, `actif` 
FROM (`machinesa`) LIMIT 50

====================================================================================================
$this->selects['vue_tickets'] = "SELECT `tickets`.`id` as id, `tickets`.`date` as date, `tickets`.`quantite`, `tickets`.`description` as description, `tickets`.`pilote`, `achat`, `achats`.`vol`, `type`, `mnom`, `mprenom` FROM (`tickets`, `membres`, `achats`) WHERE `tickets`.`pilote` = membres.mlogin and tickets.achat = achats.id ORDER BY `tickets`.`date` LIMIT 50";
// FROM=`tickets`, `membres`, `achats`
// tables: tickets, membres, achats

SELECT= `tickets`.`id` as id, `tickets`.`date` as date, `tickets`.`quantite`, `tickets`.`description` as description, `tickets`.`pilote`, `achat`, `achats`.`vol`, `type`, `mnom`, `mprenom`
    $this->real_table[`vue_tickets`][`id`] = `tickets`;
    $this->real_field[`vue_tickets`][`id`] = `id`;
    $this->real_table[`vue_tickets`][`date`] = `tickets`;
    $this->real_field[`vue_tickets`][`date`] = `date`;
    $this->real_table[`vue_tickets`][`tickets`.`quantite`] = `unknown`;
    $this->real_field[`vue_tickets`][`tickets`.`quantite`] = `tickets`.`quantite`;
    $this->real_table[`vue_tickets`][`description`] = `tickets`;
    $this->real_field[`vue_tickets`][`description`] = `description`;
    $this->real_table[`vue_tickets`][`tickets`.`pilote`] = `unknown`;
    $this->real_field[`vue_tickets`][`tickets`.`pilote`] = `tickets`.`pilote`;
    $this->real_table[`vue_tickets`][`achat`] = `tickets`;
    $this->real_field[`vue_tickets`][`achat`] = `achat`;
    $this->real_table[`vue_tickets`][`achats`.`vol`] = `unknown`;
    $this->real_field[`vue_tickets`][`achats`.`vol`] = `achats`.`vol`;
    $this->real_table[`vue_tickets`][`type`] = `tickets`;
    $this->real_field[`vue_tickets`][`type`] = `type`;
    $this->real_table[`vue_tickets`][`mnom`] = `membres`;
    $this->real_field[`vue_tickets`][`mnom`] = `mnom`;
    $this->real_table[`vue_tickets`][`mprenom`] = `membres`;
    $this->real_field[`vue_tickets`][`mprenom`] = `mprenom`;

## Dépenses par année	
select ecritures.id, ecritures.annee_exercise, date_op, sum(montant) as montant, ecritures.description, num_cheque, quantite, achat, prix, gel, ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1, ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2
from ecritures, comptes as compte1, comptes as compte2
where ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id
and YEAR(date_op) = "2011"
and compte1.codec >= "6" and compte1.codec < "7"
group by compte1
order by code1

Il suffit de ne pas grouper pour avoir le total

#Recettes
select ecritures.id, ecritures.annee_exercise, date_op, sum(montant) as montant , ecritures.description, num_cheque, quantite, achat, prix, gel, ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1, ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2
from ecritures, comptes as compte1, comptes as compte2
where ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id
and YEAR(date_op) = "2011"

and compte2.codec >= "7" and compte2.codec < "8"
group by compte2
order by code2

## Liste des duplications sur un compte

23 = CDN

entrée:
-------
select ecritures.id, ecritures.annee_exercise, date_op, montant, ecritures.description, num_cheque,  achat,  ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1, ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2
from ecritures, comptes as compte1, comptes as compte2
where ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id
and YEAR(date_op) = "2013"
and compte1 = "23"
order by montant
limit 0, 1000

Sorties
-------
select ecritures.id, ecritures.annee_exercise, date_op, montant, ecritures.description, num_cheque,  achat,  ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1, ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2
from ecritures, comptes as compte1, comptes as compte2
where ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id
and YEAR(date_op) = "2013"
and compte2 = "23"
order by montant
limit 0, 1000

select ecritures.id, ecritures.annee_exercise, date_op, MONTH(date_op) as month, sum(montant) as montant, ecritures.description, num_cheque,  achat,  ecritures.compte1, compte1.nom as nom_compte1, compte1.codec as code1, ecritures.compte2, compte2.nom as nom_compte2, compte2.codec as code2
from ecritures, comptes as compte1, comptes as compte2
where ecritures.compte1 = compte1.id and ecritures.compte2 = compte2.id
and YEAR(date_op) = "2013"
and compte1 = "23"
order by date_op
group by month
limit 0, 1000

## Events

select events_types.id as event_type, events_types.name
from events_types
where events_types.name = 'Gain de 1000m'

select events.emlogin, events.etype, events_types.id as event_type, events_types.name
from events, events_types
where events.etype = events_types.id
and events.emlogin = 'ABARR'
and events_types.name = 'Visite médical'

Openflyers
select flight.id as flight_id,aircraft_id,start_date,duration,counter_departure,counter_arrival,comments,pilot_id,name,first_name,last_name
from flight, flight_pilot,person
where flight_pilot.flight_id=flight.id
and flight_pilot.pilot_id=person.id
and aircraft_id="5"

## VENTES

SELECT `achats`.`id` as id, `achats`.`date` as date, `tarifs`.`reference` as produit, sum(quantite) as quantite, AVG(achats.prix) as prix_unit, sum(achats.prix * quantite) as prix
FROM (`achats`, `tarifs`)
WHERE `achats`.`produit` = tarifs.reference
AND YEAR(achats.date) = 2014
GROUP BY `achats`.`produit`
ORDER BY `achats`.`date`, `tarifs`.`reference`


SELECT date, produit, prix, sum(quantite) as quantite,  pilote, sum(quantite) * prix as total
FROM `achats`
where (year(date) = 2014)
and (produit = "Forfait heures" 
    or produit = "Heure de vol biplace"
    or produit = "Heure de vol forfait"
    or produit = "Heure de vol Pégase"
    or produit = "Heure de vol Piwi")
group by pilote, produit
order by pilote, total

create view paiement
()
as SELECT date, produit, prix, sum(quantite) as quantite,  pilote, sum(quantite) * prix as total
FROM `achats`
where (year(date) = 2014)
and (produit = "Forfait heures" 
    or produit = "Heure de vol biplace"
    or produit = "Heure de vol forfait"
    or produit = "Heure de vol Pégase"
    or produit = "Heure de vol Piwi")
group by pilote, produit
order by pilote, total) as tmp

#######################################################################################################
Création d'une base de test

DELETE FROM `gvv2`.`achats`
DELETE FROM `gvv2`.`comptes` WHERE `comptes`.`codec` = 411;
DELETE FROM `gvv2`.`ecritures`
DELETE FROM `gvv2`.`events`
DELETE FROM `gvv2`.`volsa`
DELETE FROM `gvv2`.`volsp`
DELETE FROM `gvv2`.`licences`
DELETE FROM `gvv2`.`mails`
DELETE FROM `gvv2`.`membres`
DELETE FROM `gvv2`.`reports`
DELETE FROM `gvv2`.`tickets`

=====================================================================
select nom, codec, desc from comptes;

SELECT nom,pcode, pdesc,codec,comptes.desc from comptes,planc
where planc.pcode=comptes.codec

SELECT nom, concat(pcode, ' ', pdesc) as code,comptes.desc from comptes,planc
where planc.pcode=comptes.codec


#######################################################################################################
sum hours: 
SELECT `vpduree`, `mdaten`, `vpnbkm`, SUM(`vpduree`) AS vpduree
FROM (`volsp`, `membres`, `machinesp`)
WHERE `volsp`.`vppilid` = membres.mlogin and volsp.vpmacid = machinesp.mpimmat
AND YEAR(vpdate) = "2020"

SELECT `vpduree`, `mdaten`, `vpnbkm`, SUM(`vpnbkm`) AS vpnbkm
FROM (`volsp`, `membres`, `machinesp`)
WHERE `volsp`.`vppilid` = membres.mlogin and volsp.vpmacid = machinesp.mpimmat
AND YEAR(vpdate) = "2020"

SELECT `vpduree`, `mdaten`, `vpnbkm`, SUM(`vpduree`) AS vpduree
FROM (`volsp`, `membres`, `machinesp`)
WHERE `volsp`.`vppilid` = membres.mlogin and volsp.vpmacid = machinesp.mpimmat
AND YEAR(vpdate) = "2020"
AND `mdaten` > '1995-01-01'

SELECT `vpduree`, `mdaten`, `vpnbkm`, SUM(`vpduree`) AS vpduree
FROM (`volsp`, `membres`, `machinesp`)
WHERE `volsp`.`vppilid` = membres.mlogin and volsp.vpmacid = machinesp.mpimmat
AND YEAR(vpdate) = "2020"
AND `vpdc` =  1

SELECT `vpduree`, `mdaten`, `vpnbkm`, SUM(`vpduree`) AS total
FROM (`volsp`, `membres`, `machinesp`)
WHERE `volsp`.`vppilid` = membres.mlogin and volsp.vpmacid = machinesp.mpimmat
AND YEAR(vpdate) = "2020"
GROUP BY  `total`
LIMIT 0, 25

SELECT ANY_VALUE(`vpid`),ANY_VALUE(`vpduree`), ANY_VALUE(`mdaten`), ANY_VALUE(`vpnbkm`), SUM(`vpduree`) AS total
FROM (`volsp`, `membres`, `machinesp`)
WHERE `volsp`.`vppilid` = membres.mlogin and volsp.vpmacid = machinesp.mpimmat
AND YEAR(vpdate) = "2020" 
LIMIT 0,25