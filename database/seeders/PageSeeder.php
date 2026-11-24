<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Pages éditables depuis l'administration.
 * Contenu repris de l'ancien site (2012-2025) et des anciens sites FoliaMusica.
 * À relire : tarifs, noms des administrateurs, coordonnées.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            // Ne jamais écraser une page déjà modifiée dans l'administration.
            Page::firstOrCreate(['slug' => $slug], $page + ['is_system' => true, 'is_published' => true]);
        }
    }

    private function pages(): array
    {
        return [
            'accueil' => [
                'title' => 'Bienvenue',
                'menu_title' => 'Accueil',
                'meta_description' => 'Les Amis des Concerts du Dimanche Matin : musique de chambre chaque 2e dimanche du mois à 11h au musée de la Boverie, à Liège.',
                'content' => <<<'HTML'
<p>Tous les deuxièmes dimanches du mois (sauf juillet et août) à 11h, les Amis des Concerts du Dimanche Matin accueillent des musiciens et artistes chevronnés de nos Conservatoires royaux ainsi que du monde entier, pour un récital d'une heure de musique classique de tous les styles et de tous les siècles.</p>
<p>Avec l'aide de la Fédération Wallonie-Bruxelles et du service des affaires culturelles de la Province de Liège, la directrice artistique Cécile Leleux engage solistes, chambristes et orchestres pour faire découvrir des musiques du monde, de toutes les époques et des créations d'exception, dans un esprit de convivialité.</p>
HTML,
            ],

            'infos-pratiques' => [
                'title' => 'Tarifs et réservations',
                'menu_title' => 'Infos pratiques',
                'meta_description' => 'Tarifs, carte d\'adhérent et réservation des Concerts du Dimanche Matin à Liège.',
                'content' => <<<'HTML'
<h2>Tarifs</h2>
<ul>
<li><strong>Entrée au concert : 15 €</strong></li>
<li><strong>Carte d'adhérent : 20 €</strong> — elle donne droit à une réduction de 5 € par concert</li>
<li><strong>Étudiants de moins de 25 ans : 5 €</strong></li>
<li>Enfants de moins de 12 ans : gratuit</li>
</ul>
<h2>Réservation obligatoire</h2>
<p>Par téléphone au <a href="tel:+32499259530">0499 25 95 30</a>, ou par e-mail à <a href="mailto:cecile.leleux.be@gmail.com">cecile.leleux.be@gmail.com</a> ou <a href="mailto:valdescamps@hotmail.com">valdescamps@hotmail.com</a>.</p>
<h2>Accès</h2>
<p>Auditorium du musée de la Boverie, Parc de la Boverie 3, 4020 Liège. Accès PMR.</p>
<p><em>Les concerts FoliaMusica ont leurs propres conditions (souvent gratuits) : elles sont indiquées sur chaque concert.</em></p>
HTML,
            ],

            'historique' => [
                'title' => 'Des Concerts du Dimanche Matin à l\'ACDM',
                'menu_title' => 'Notre histoire',
                'meta_description' => 'L\'histoire des Concerts du Dimanche Matin à Liège, depuis 1950.',
                'content' => <<<'HTML'
<p>Le 17 septembre 1950, à 11h, « Liège se réveille au son du quatuor à cordes », écrit Marcel Deprez. Qui sont-ils ? Henri Koch, Emmanuel Koch (violons), Louis Poulet (alto), Eric Feldbush (violoncelle). Le groupe prend définitivement le nom de Quatuor municipal de Liège ; ils vont alors commencer toute une série de concerts d'un genre nouveau dans la région liégeoise : les Concerts du Dimanche Matin. Leur but principal est de remettre à l'honneur une forme musicale délaissée par les organisateurs de concerts : la musique de chambre, et plus particulièrement le quatuor.</p>
<blockquote><p>« Il convient de rendre aux membres du quatuor municipal de Liège, qui en furent les courageux artisans, l'hommage que méritent leurs efforts. Ils ont exécuté, déchiffré, mis sur pied et conduit au degré de perfection qu'exige l'exécution publique, 48 quatuors… travail consenti gratuitement par chacun d'eux… On conviendra que cet effort exceptionnel mérite un large coup de chapeau. » — Jules Bosman</p></blockquote>
<p>Emmanuel Koch a pris la relève ; le quatuor a bien sûr fonctionné avec des mousquetaires différents, mais Emmanuel en est toujours resté le timonier et l'âme. Le quatuor a rendu vie au Vertbois.</p>
<p>Dès les années 1970, Jean Duchesne et son équipe, avec Emmanuel Koch, s'installent au MAMAC (aujourd'hui musée de la Boverie), aidés de monsieur Magotte, alors échevin de la Culture de la Ville de Liège : les récitals deviennent « Les Amis des Concerts du Dimanche Matin », le 2<sup>e</sup> dimanche du mois. Des artistes de renommée internationale s'y produisent : Emmanuel Koch, Igor Oïstrakh, Jörg Demus, Augustin Dumay…</p>
<p>Dans le même esprit de continuité et avec l'aide de la Fédération Wallonie-Bruxelles et du service des affaires culturelles de la Province de Liège, la directrice artistique Cécile Leleux, devenant présidente de l'ACDM, remodèle l'asbl sous la houlette de monsieur Magotte et de sa secrétaire Hélène Stanzos, qui deviendra secrétaire-trésorière de l'asbl avant d'être remplacée par Valérie Descamps. Cette équipe de bénévoles permet, avec dix concerts par an, d'engager de 40 à 60 musiciens.</p>
<p>Cécile Leleux poursuit son travail de fourmi après les travaux du MAMAC : au BAL, au musée Curtius avec l'aide de l'échevin de la Culture, monsieur Hupkens, puis au musée de la Vie wallonne grâce à sa directrice, madame Jadot. En 2020, l'ACDM déménage à la Villa Consulaire avec l'aide du consul honoraire de France, monsieur Cloes, pour enfin se retrouver en 2023 au musée de la Boverie grâce à l'échevin de la Culture, monsieur Aydogdu.</p>
<p>Cécile Leleux engage solistes, chambristes et orchestres pour faire découvrir des musiques du monde, de toutes les époques et des créations d'exception, dans un esprit de convivialité où se retrouvent les artistes, avec une grande place aux musiciens belges.</p>
HTML,
            ],

            'foliamusica' => [
                'title' => 'FoliaMusica',
                'menu_title' => 'FoliaMusica',
                'meta_description' => 'FoliaMusica : des concerts exceptionnels à Liège et ailleurs, sous la direction artistique de Cécile Leleux.',
                'content' => <<<'HTML'
<p class="lead">« Une musique qui vous tend la main »</p>
<p>FoliaMusica, c'est là où se retrouvent des artistes de tous horizons : musiciens du monde, issus des académies de musique, des conservatoires régionaux et royaux, lauréats de concours nationaux et internationaux. Sous la direction artistique de Cécile Leleux, FoliaMusica organise des concerts où elle le souhaite, quand elle le souhaite : au Palais provincial, dans les églises de Liège, en plein air…</p>
<h2>Folia, Musica</h2>
<p><strong>Folia</strong> — les feuilles : la folie de l'artiste, l'écoute intérieure partagée ; l'artiste et le public, joie et bonheur ; la naissance d'une feuille, une vivacité éphémère, une feuille d'automne ; une folie musicale.</p>
<p><strong>Musica</strong> — la musique : une sonorité à écouter et à découvrir ; la découverte artistique d'un talent ; la rencontre avec les artistes ; la naissance d'une œuvre !</p>
<p>FoliaMusica, folie de la musique, qui naquit aux confins du Périgord, à Pluviers et Saint-Barthélemy-de-Bussière, jeta un pont en Limousin, au bord de la Vienne, au Poudrier, et s'amuse en bordure de Meuse à Liège… FoliaMusica, qui sait où sera demain ton destin !</p>
<h2>Le Dextuor</h2>
<p>De FoliaMusica est né le Dextuor, un orchestre de chambre de jeunes musiciens issus des académies et des conservatoires royaux de Belgique, réunis sous la baguette de chefs d'orchestre et de chefs de pupitre professionnels, dont Thomas Van Haeperen et Alain Voirpy.</p>
HTML,
            ],

            'musiciens' => [
                'title' => 'Musiciens : vous produire à l\'ACDM',
                'menu_title' => 'Musiciens',
                'meta_description' => 'Musicien professionnel, chambriste ou soliste ? Proposez un récital aux Concerts du Dimanche Matin (musée de la Boverie) ou avec FoliaMusica, à Liège.',
                'content' => <<<'HTML'
<p>Depuis 1950, les Concerts du Dimanche Matin accueillent à Liège solistes, chambristes et orchestres, avec une grande place faite aux musiciens belges et aux artistes issus de nos conservatoires royaux, mais aussi des musiciens venus du monde entier.</p>
<h2>Deux cadres pour vous produire</h2>
<ul>
<li><strong>ACDM</strong> — un récital d'environ une heure, le 2<sup>e</sup> dimanche du mois à 11h, à l'auditorium du musée de la Boverie, devant un public fidèle de mélomanes.</li>
<li><strong>FoliaMusica</strong> — des concerts à d'autres dates et dans d'autres lieux : Palais provincial, églises de Liège…</li>
</ul>
<h2>Proposer un concert</h2>
<p>Envoyez votre proposition à la directrice artistique, Cécile Leleux, à <a href="mailto:cecile.leleux.be@gmail.com">cecile.leleux.be@gmail.com</a>, avec :</p>
<ul>
<li>la présentation de votre formation et une courte biographie de chaque musicien ;</li>
<li>le programme proposé (environ une heure de musique) ;</li>
<li>des liens vers des enregistrements ou des vidéos ;</li>
<li>vos périodes de disponibilité.</li>
</ul>
<p>La programmation est établie longtemps à l'avance : n'hésitez pas à nous écrire tôt.</p>
<p><a href="/artistes">Découvrez les musiciens qui se sont déjà produits chez nous.</a></p>
HTML,
            ],

            'contact' => [
                'title' => 'Nous contacter',
                'menu_title' => 'Contact',
                'meta_description' => 'Contacter l\'ACDM et FoliaMusica, réserver un concert.',
                'content' => <<<'HTML'
<h2>Réserver</h2>
<p>Téléphone : <a href="tel:+32499259530">0499 25 95 30</a><br>E-mail : <a href="mailto:cecile.leleux.be@gmail.com">cecile.leleux.be@gmail.com</a> ou <a href="mailto:valdescamps@hotmail.com">valdescamps@hotmail.com</a></p>
<h2>L'association</h2>
<p>Présidente et directrice artistique : Cécile Leleux<br>Secrétaire-trésorière : Valérie Descamps</p>
<p>ASBL Les Amis des Concerts du Dimanche Matin<br>Rue Auguste-Hock 4, 4020 Liège<br>Numéro d'entreprise : BE 0452.638.226</p>
HTML,
            ],

            'mentions-legales' => [
                'title' => 'Mentions légales et conditions générales',
                'menu_title' => 'Mentions légales',
                'meta_description' => 'Mentions légales et conditions générales du site de l\'ACDM.',
                'content' => <<<'HTML'
<h2>Éditeur du site</h2>
<p><strong>ASBL Les Amis des Concerts du Dimanche Matin</strong> (ACDM)<br>Association sans but lucratif de droit belge<br>Siège : Rue Auguste-Hock 4, 4020 Liège, Belgique<br>Numéro d'entreprise (BCE) : 0452.638.226 — RPM Liège, division Liège<br>Contact : <a href="mailto:cecile.leleux.be@gmail.com">cecile.leleux.be@gmail.com</a> — <a href="tel:+32499259530">0499 25 95 30</a></p>
<p>FoliaMusica est le nom sous lequel l'ACDM présente ses concerts organisés en dehors des Concerts du Dimanche Matin. Les concerts FoliaMusica sont soumis aux mêmes conditions que ceux de l'ACDM.</p>
<h2>Hébergement</h2>
<p>OVH SAS, 2 rue Kellermann, 59100 Roubaix, France — <a href="https://www.ovhcloud.com">www.ovhcloud.com</a></p>
<h2>Conditions générales</h2>
<h3>1. Objet</h3>
<p>Les présentes conditions s'appliquent à l'utilisation du site et à la participation aux concerts organisés par l'ACDM et présentés sous les noms « ACDM » et « FoliaMusica ».</p>
<h3>2. Informations publiées</h3>
<p>Les programmes, dates, lieux, horaires et interprètes sont publiés de bonne foi. Ils peuvent être modifiés en cas de force majeure ou d'indisponibilité d'un artiste ; l'ACDM s'efforce alors d'en informer le public au plus vite sur ce site et dans sa newsletter.</p>
<h3>3. Réservations et paiement</h3>
<p>La réservation se fait par téléphone ou par e-mail auprès de l'association. Sauf mention contraire, le paiement s'effectue sur place, le jour du concert. Les tarifs en vigueur sont indiqués sur la page « Infos pratiques » et, le cas échéant, sur chaque concert.</p>
<h3>4. Annulation ou report d'un concert</h3>
<p>En cas d'annulation d'un concert par l'ACDM, les places déjà payées sont remboursées ou reportées sur un autre concert, au choix du spectateur. Un changement d'interprète ou de programme ne donne pas lieu à remboursement lorsque le concert est maintenu.</p>
<h3>5. Déroulement des concerts</h3>
<p>Les spectateurs sont invités à arriver quelques minutes avant le début du concert. Les enregistrements audio et vidéo sont interdits sans l'accord des artistes. L'ACDM peut prendre des photographies pendant les concerts pour illustrer ses activités (site, galerie, réseaux sociaux) ; toute personne qui ne souhaite pas apparaître sur une photo publiée peut en demander le retrait à l'adresse ci-dessus.</p>
<h3>6. Propriété intellectuelle</h3>
<p>Les textes, logos, photographies et affiches publiés sur ce site appartiennent à l'ACDM ou à leurs auteurs respectifs. Toute reproduction nécessite une autorisation préalable, sauf pour la presse dans le cadre de l'annonce des concerts.</p>
<h3>7. Responsabilité</h3>
<p>L'ACDM ne peut être tenue responsable des dommages résultant de l'utilisation du site ni du contenu des sites externes vers lesquels il renvoie.</p>
<h3>8. Droit applicable</h3>
<p>Les présentes conditions sont soumises au droit belge. Tout litige relève des tribunaux de l'arrondissement judiciaire de Liège.</p>
HTML,
            ],

            'vie-privee' => [
                'title' => 'Protection des données personnelles',
                'menu_title' => 'Vie privée',
                'meta_description' => 'Comment l\'ACDM traite vos données personnelles (RGPD).',
                'content' => <<<'HTML'
<h2>Responsable du traitement</h2>
<p>ASBL Les Amis des Concerts du Dimanche Matin, Rue Auguste-Hock 4, 4020 Liège — BCE 0452.638.226.<br>Contact : <a href="mailto:cecile.leleux.be@gmail.com">cecile.leleux.be@gmail.com</a></p>
<h2>Quelles données, pourquoi ?</h2>
<p><strong>Newsletter.</strong> Si vous vous abonnez, nous enregistrons votre adresse e-mail, la date de votre inscription et de sa confirmation, ainsi que l'adresse IP utilisée lors de l'inscription (preuve de votre consentement). Ces données servent uniquement à vous envoyer le programme de nos concerts. Base légale : votre consentement (art. 6.1.a du RGPD).</p>
<p><strong>Réservations.</strong> Lorsque vous réservez par téléphone ou par e-mail, votre nom et vos coordonnées sont utilisés uniquement pour gérer votre réservation.</p>
<p>Nous ne vendons, ne louons et ne cédons vos données à personne. Elles ne sont utilisées à aucune fin publicitaire.</p>
<h2>Durée de conservation</h2>
<p>Votre adresse est conservée tant que vous êtes abonné. Lorsque vous vous désinscrivez, nous ne conservons que l'adresse et la date de désinscription, afin de ne plus vous écrire. Une inscription non confirmée est supprimée après 30 jours.</p>
<h2>Vos droits</h2>
<p>Vous pouvez à tout moment vous désinscrire grâce au lien présent dans chaque newsletter, et demander l'accès, la rectification ou l'effacement de vos données en écrivant à l'adresse ci-dessus. Vous pouvez aussi introduire une réclamation auprès de l'Autorité de protection des données (<a href="https://www.autoriteprotectiondonnees.be">www.autoriteprotectiondonnees.be</a>).</p>
<h2>Cookies</h2>
<p>Ce site n'utilise ni cookie publicitaire ni outil de mesure d'audience. Seuls des cookies techniques, indispensables au fonctionnement des formulaires et de l'espace d'administration, sont déposés.</p>
<h2>Sous-traitants</h2>
<p>Le site et les e-mails sont hébergés par OVH SAS (France, Union européenne).</p>
HTML,
            ],
        ];
    }
}
