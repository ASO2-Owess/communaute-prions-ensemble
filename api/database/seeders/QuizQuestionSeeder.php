<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Les 67 questions du quiz general, reprises du prototype.
 *
 * Cote serveur : corriger une faute ne demande plus de republier l'application.
 */
class QuizQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ($this->rows() as $row) {
            DB::table('quiz_questions')->insertOrIgnore([
                'question' => $row['question'],
                'options' => json_encode($row['options'], JSON_UNESCAPED_UNICODE),
                'correct_index' => $row['correct_index'],
                'published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        return [
            ['question' => 'Qui a créé les cieux et la terre selon Genèse 1 ?', 'options' => ['Moïse', 'Dieu', 'Un ange', 'Adam'], 'correct_index' => 1],
            ['question' => 'Combien de commandements Dieu donne-t-il à Moïse sur le Sinaï ?', 'options' => ['Sept', 'Douze', 'Dix', 'Trois'], 'correct_index' => 2],
            ['question' => 'Quel ange annonce à Marie qu\'elle enfantera Jésus ?', 'options' => ['Michel', 'Gabriel', 'Raphaël', 'Uriel'], 'correct_index' => 1],
            ['question' => 'Selon Jean 1, qui est « la Parole faite chair » ?', 'options' => ['Jean-Baptiste', 'Moïse', 'Jésus', 'Élie'], 'correct_index' => 2],
            ['question' => 'Dans le Sermon sur la montagne, que dit Jésus à propos des disciples ?', 'options' => ['Vous êtes la loi', 'Vous êtes le sel et la lumière', 'Vous êtes les rois', 'Vous êtes les prêtres'], 'correct_index' => 1],
            ['question' => 'Quel est le premier livre de l\'Ancien Testament ?', 'options' => ['Exode', 'Lévitique', 'Genèse', 'Josué'], 'correct_index' => 2],
            ['question' => 'Combien y a-t-il de livres dans le Nouveau Testament ?', 'options' => ['27', '39', '66', '24'], 'correct_index' => 0],
            ['question' => 'Dans le Psaume 23, à quoi Dieu est-il comparé ?', 'options' => ['Un roi', 'Un berger', 'Un juge', 'Un guerrier'], 'correct_index' => 1],
            ['question' => 'Qui tente Ève dans le jardin d\'Éden ?', 'options' => ['Un ange déchu', 'Le serpent', 'Un voisin', 'Caïn'], 'correct_index' => 1],
            ['question' => 'D\'où Dieu parle-t-il à Moïse dans Exode 3 ?', 'options' => ['Une montagne', 'Un buisson ardent', 'Une tempête', 'Un temple'], 'correct_index' => 1],
            ['question' => 'Qui est le père de la foi, appelé ami de Dieu ?', 'options' => ['Isaac', 'Abraham', 'Jacob', 'Moïse'], 'correct_index' => 1],
            ['question' => 'Combien de jours et de nuits a duré le déluge (pluie) selon Genèse ?', 'options' => ['7', '40', '100', '150'], 'correct_index' => 1],
            ['question' => 'Qui a interprété les rêves de Pharaon en Égypte ?', 'options' => ['Joseph', 'Moïse', 'Daniel', 'Josué'], 'correct_index' => 0],
            ['question' => 'Quel est le successeur de Moïse à la tête d\'Israël ?', 'options' => ['Caleb', 'Josué', 'Aaron', 'Samuel'], 'correct_index' => 1],
            ['question' => 'Quelle juge et prophétesse dirige Israël dans le livre des Juges ?', 'options' => ['Ruth', 'Débora', 'Esther', 'Miriam'], 'correct_index' => 1],
            ['question' => 'Qui tue le géant Goliath ?', 'options' => ['Saül', 'Jonathan', 'David', 'Samuel'], 'correct_index' => 2],
            ['question' => 'Quel roi d\'Israël est réputé pour sa sagesse ?', 'options' => ['Saül', 'David', 'Salomon', 'Achab'], 'correct_index' => 2],
            ['question' => 'Dans quelle fosse Daniel est-il jeté ?', 'options' => ['Un puits', 'Une fosse aux lions', 'Une fournaise', 'Une prison'], 'correct_index' => 1],
            ['question' => 'Quel prophète est avalé par un grand poisson ?', 'options' => ['Élie', 'Jonas', 'Ézéchiel', 'Amos'], 'correct_index' => 1],
            ['question' => 'Où Jésus est-il né ?', 'options' => ['Nazareth', 'Jérusalem', 'Bethléem', 'Capharnaüm'], 'correct_index' => 2],
            ['question' => 'Qui baptise Jésus dans le Jourdain ?', 'options' => ['Pierre', 'Jean-Baptiste', 'André', 'Philippe'], 'correct_index' => 1],
            ['question' => 'Combien de disciples Jésus choisit-il comme apôtres ?', 'options' => ['7', '10', '12', '14'], 'correct_index' => 2],
            ['question' => 'Quel apôtre renie Jésus trois fois ?', 'options' => ['Jean', 'Thomas', 'Pierre', 'André'], 'correct_index' => 2],
            ['question' => 'Quel apôtre doute de la résurrection avant de voir Jésus ?', 'options' => ['Thomas', 'Philippe', 'Matthieu', 'Jacques'], 'correct_index' => 0],
            ['question' => 'Qui persécute les chrétiens avant sa conversion sur le chemin de Damas ?', 'options' => ['Pierre', 'Étienne', 'Saul (Paul)', 'Barnabas'], 'correct_index' => 2],
            ['question' => 'Quel événement est célébré à la Pentecôte en Actes 2 ?', 'options' => ['La résurrection', 'La descente du Saint-Esprit', 'L\'ascension', 'La transfiguration'], 'correct_index' => 1],
            ['question' => 'Quel est le dernier livre de la Bible ?', 'options' => ['Jude', '2 Pierre', 'Apocalypse', 'Malachie'], 'correct_index' => 2],
            ['question' => 'Quel est le plus court verset de la Bible (thème) ?', 'options' => ['« Dieu est amour »', '« Jésus pleura »', '« Priez sans cesse »', '« Veillez donc »'], 'correct_index' => 1],
            ['question' => 'Qui écrit la majorité des épîtres du Nouveau Testament ?', 'options' => ['Pierre', 'Jean', 'Paul', 'Jacques'], 'correct_index' => 2],
            ['question' => 'Quel est le premier miracle de Jésus selon l\'évangile de Jean ?', 'options' => ['Guérir un aveugle', 'Changer l\'eau en vin', 'Marcher sur l\'eau', 'Multiplier les pains'], 'correct_index' => 1],
            ['question' => 'Quelle femme accueille Jésus et se plaint que sa sœur ne l\'aide pas ?', 'options' => ['Marie-Madeleine', 'Marthe', 'Anne', 'Élisabeth'], 'correct_index' => 1],
            ['question' => 'Qui est le mari d\'Esther, roi de Perse ?', 'options' => ['Cyrus', 'Assuérus (Xerxès)', 'Darius', 'Nabuchodonosor'], 'correct_index' => 1],
            ['question' => 'Combien de jours et de nuits dure le déluge selon Genèse 7 ?', 'options' => ['7', '40', '70', '150'], 'correct_index' => 1],
            ['question' => 'Quel est le premier livre de la Bible ?', 'options' => ['Exode', 'Job', 'Genèse', 'Psaumes'], 'correct_index' => 2],
            ['question' => 'Quel fils d\'Isaac vend son droit d\'aînesse pour un plat de lentilles ?', 'options' => ['Jacob', 'Ésaü', 'Ruben', 'Lévi'], 'correct_index' => 1],
            ['question' => 'Combien de fils Jacob a-t-il, à l\'origine des tribus d\'Israël ?', 'options' => ['10', '11', '12', '14'], 'correct_index' => 2],
            ['question' => 'Qui est vendu par ses frères et devient gouverneur d\'Égypte ?', 'options' => ['Benjamin', 'Joseph', 'Juda', 'Siméon'], 'correct_index' => 1],
            ['question' => 'Quelle mer Dieu ouvre-t-il devant Moïse et le peuple ?', 'options' => ['La mer Morte', 'La mer Rouge', 'La mer de Galilée', 'La Méditerranée'], 'correct_index' => 1],
            ['question' => 'Quel aliment Dieu envoie-t-il chaque matin au désert ?', 'options' => ['La manne', 'Le pain sans levain', 'Des figues', 'Du miel'], 'correct_index' => 0],
            ['question' => 'Qui succède à Moïse pour faire entrer Israël en Canaan ?', 'options' => ['Aaron', 'Caleb', 'Josué', 'Éléazar'], 'correct_index' => 2],
            ['question' => 'Quelle ville s\'écroule après sept tours des Israélites ?', 'options' => ['Aï', 'Jéricho', 'Hébron', 'Sichem'], 'correct_index' => 1],
            ['question' => 'Quelle femme moabite dit : « Où tu iras j\'irai » ?', 'options' => ['Naomi', 'Ruth', 'Orpa', 'Abigaïl'], 'correct_index' => 1],
            ['question' => 'Qui tue le géant Goliath avec une pierre ?', 'options' => ['Saül', 'Jonathan', 'David', 'Abner'], 'correct_index' => 2],
            ['question' => 'Quel roi bâtit le premier Temple à Jérusalem ?', 'options' => ['David', 'Salomon', 'Josias', 'Ézéchias'], 'correct_index' => 1],
            ['question' => 'Quel prophète est enlevé au ciel sur un char de feu ?', 'options' => ['Élisée', 'Énoch', 'Élie', 'Ésaïe'], 'correct_index' => 2],
            ['question' => 'Combien de livres compte la Bible protestante ?', 'options' => ['39', '27', '66', '73'], 'correct_index' => 2],
            ['question' => 'Quel livre est composé de 150 chants et prières ?', 'options' => ['Proverbes', 'Psaumes', 'Cantique des Cantiques', 'Lamentations'], 'correct_index' => 1],
            ['question' => 'Qui a écrit la majeure partie des Psaumes ?', 'options' => ['Salomon', 'Moïse', 'David', 'Asaph'], 'correct_index' => 2],
            ['question' => 'Quel homme perd tout mais garde sa foi en Dieu ?', 'options' => ['Job', 'Jérémie', 'Osée', 'Habacuc'], 'correct_index' => 0],
            ['question' => 'Quel prophète est appelé alors qu\'il est encore jeune, en disant « me voici » ?', 'options' => ['Samuel', 'Amos', 'Michée', 'Aggée'], 'correct_index' => 0],
            ['question' => 'Combien de compagnons de Daniel sont jetés dans la fournaise ?', 'options' => ['Deux', 'Trois', 'Quatre', 'Sept'], 'correct_index' => 1],
            ['question' => 'Qui annonce à Marie qu\'elle enfantera Jésus ?', 'options' => ['Gabriel', 'Michel', 'Raphaël', 'Un prophète'], 'correct_index' => 0],
            ['question' => 'Combien de temps Jésus jeûne-t-il au désert ?', 'options' => ['7 jours', '21 jours', '40 jours', '3 jours'], 'correct_index' => 2],
            ['question' => 'Combien de pains servent à nourrir cinq mille hommes ?', 'options' => ['Trois', 'Cinq', 'Sept', 'Douze'], 'correct_index' => 1],
            ['question' => 'Sur quelle montagne Jésus prononce-t-il les Béatitudes ?', 'options' => ['Le Sinaï', 'Le Carmel', 'Une montagne de Galilée', 'Le mont des Oliviers'], 'correct_index' => 2],
            ['question' => 'Quel disciple trahit Jésus pour trente pièces d\'argent ?', 'options' => ['Pierre', 'Judas Iscariot', 'Thomas', 'Simon le Zélote'], 'correct_index' => 1],
            ['question' => 'Dans quel jardin Jésus prie-t-il avant son arrestation ?', 'options' => ['Éden', 'Gethsémané', 'Béthanie', 'Siloé'], 'correct_index' => 1],
            ['question' => 'Qui porte la croix de Jésus sur le chemin du Calvaire ?', 'options' => ['Simon de Cyrène', 'Joseph d\'Arimathée', 'Nicodème', 'Barabbas'], 'correct_index' => 0],
            ['question' => 'Le troisième jour, que découvrent les femmes venues au tombeau ?', 'options' => ['Un ange endormi', 'Le tombeau vide', 'Les soldats partis', 'Une pierre scellée'], 'correct_index' => 1],
            ['question' => 'Combien de jours séparent la résurrection de l\'ascension selon Actes 1 ?', 'options' => ['3', '12', '40', '50'], 'correct_index' => 2],
            ['question' => 'Quel diacre est le premier martyr chrétien ?', 'options' => ['Étienne', 'Philippe', 'Barnabas', 'Timothée'], 'correct_index' => 0],
            ['question' => 'Dans quelle ville les disciples sont-ils appelés « chrétiens » pour la première fois ?', 'options' => ['Jérusalem', 'Antioche', 'Éphèse', 'Rome'], 'correct_index' => 1],
            ['question' => 'À quelle église Paul écrit-il le chapitre sur l\'amour (1 Co 13) ?', 'options' => ['Rome', 'Corinthe', 'Galatie', 'Philippes'], 'correct_index' => 1],
            ['question' => 'Quel fruit de l\'Esprit est cité en premier en Galates 5 ?', 'options' => ['La joie', 'La paix', 'L\'amour', 'La patience'], 'correct_index' => 2],
            ['question' => 'Selon Éphésiens 6, comment s\'appelle l\'ensemble de l\'équipement du croyant ?', 'options' => ['L\'armure de Dieu', 'Le bouclier de la foi', 'Le manteau de justice', 'L\'épée de vérité'], 'correct_index' => 0],
            ['question' => 'Combien d\'églises sont adressées dans l\'Apocalypse ?', 'options' => ['Trois', 'Cinq', 'Sept', 'Douze'], 'correct_index' => 2],
            ['question' => 'Quel verset résume : « Car Dieu a tant aimé le monde… » ?', 'options' => ['Jean 3.16', 'Romains 8.28', 'Psaume 23.1', 'Matthieu 6.33'], 'correct_index' => 0],
        ];
    }
}
