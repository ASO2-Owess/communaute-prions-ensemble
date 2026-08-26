<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Les 65 figures bibliques, en 6 categories.
 *
 * Le catalogue etait fige dans l'application. Ajouter un personnage ne devrait
 * pas demander une mise a jour publiee sur les magasins.
 *
 * Une meme personne peut figurer dans deux categories — Eve est a la fois
 * patriarche et femme de la Bible — d'ou l'unicite sur (categorie, slug).
 */
class BibleFigureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $cat) {
            DB::table('figure_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                ['label' => $cat['label'], 'position' => $cat['position']]
            );

            $categoryId = DB::table('figure_categories')
                ->where('slug', $cat['slug'])->value('id');

            foreach ($cat['people'] as $i => $person) {
                DB::table('bible_figures')->updateOrInsert(
                    ['category_id' => $categoryId, 'slug' => $person['slug']],
                    ['name' => $person['name'], 'position' => $i + 1]
                );
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        return [
            [
                'slug' => 'patriarches',
                'label' => 'Patriarches',
                'position' => 1,
                'people' => [
                    ['name' => 'Adam', 'slug' => 'adam'],
                    ['name' => 'Ève', 'slug' => 'eve'],
                    ['name' => 'Noé', 'slug' => 'noe'],
                    ['name' => 'Abraham', 'slug' => 'abraham'],
                    ['name' => 'Sara', 'slug' => 'sara'],
                    ['name' => 'Isaac', 'slug' => 'isaac'],
                    ['name' => 'Jacob', 'slug' => 'jacob'],
                    ['name' => 'Joseph', 'slug' => 'joseph'],
                ],
            ],
            [
                'slug' => 'juges',
                'label' => 'Juges d\'Israël',
                'position' => 2,
                'people' => [
                    ['name' => 'Otniel', 'slug' => 'otniel'],
                    ['name' => 'Éhud', 'slug' => 'ehud'],
                    ['name' => 'Débora', 'slug' => 'debora'],
                    ['name' => 'Gédéon', 'slug' => 'gedeon'],
                    ['name' => 'Jephté', 'slug' => 'jephte'],
                    ['name' => 'Samson', 'slug' => 'samson'],
                    ['name' => 'Samuel', 'slug' => 'samuel'],
                ],
            ],
            [
                'slug' => 'prophetes',
                'label' => 'Prophètes',
                'position' => 3,
                'people' => [
                    ['name' => 'Moïse', 'slug' => 'moise'],
                    ['name' => 'Élie', 'slug' => 'elie'],
                    ['name' => 'Élisée', 'slug' => 'elisee'],
                    ['name' => 'Ésaïe', 'slug' => 'esaie'],
                    ['name' => 'Jérémie', 'slug' => 'jeremie'],
                    ['name' => 'Ézéchiel', 'slug' => 'ezechiel'],
                    ['name' => 'Daniel', 'slug' => 'daniel'],
                    ['name' => 'Osée', 'slug' => 'osee'],
                    ['name' => 'Joël', 'slug' => 'joel'],
                    ['name' => 'Amos', 'slug' => 'amos'],
                    ['name' => 'Abdias', 'slug' => 'abdias'],
                    ['name' => 'Jonas', 'slug' => 'jonas'],
                    ['name' => 'Michée', 'slug' => 'michee'],
                    ['name' => 'Nahum', 'slug' => 'nahum'],
                    ['name' => 'Habacuc', 'slug' => 'habacuc'],
                    ['name' => 'Sophonie', 'slug' => 'sophonie'],
                    ['name' => 'Aggée', 'slug' => 'aggee'],
                    ['name' => 'Zacharie', 'slug' => 'zacharie'],
                    ['name' => 'Malachie', 'slug' => 'malachie'],
                ],
            ],
            [
                'slug' => 'rois',
                'label' => 'Rois d\'Israël',
                'position' => 4,
                'people' => [
                    ['name' => 'Saül', 'slug' => 'saul'],
                    ['name' => 'David', 'slug' => 'david'],
                    ['name' => 'Salomon', 'slug' => 'salomon'],
                    ['name' => 'Ézéchias', 'slug' => 'ezechias'],
                    ['name' => 'Josias', 'slug' => 'josias'],
                ],
            ],
            [
                'slug' => 'apotres',
                'label' => 'Apôtres',
                'position' => 5,
                'people' => [
                    ['name' => 'Pierre', 'slug' => 'pierre'],
                    ['name' => 'André', 'slug' => 'andre'],
                    ['name' => 'Jacques (fils de Zébédée)', 'slug' => 'jacques-fils-de-zebedee'],
                    ['name' => 'Jean', 'slug' => 'jean'],
                    ['name' => 'Philippe', 'slug' => 'philippe'],
                    ['name' => 'Barthélemy', 'slug' => 'barthelemy'],
                    ['name' => 'Thomas', 'slug' => 'thomas'],
                    ['name' => 'Matthieu', 'slug' => 'matthieu'],
                    ['name' => 'Jacques (fils d\'Alphée)', 'slug' => 'jacques-fils-d-alphee'],
                    ['name' => 'Thaddée', 'slug' => 'thaddee'],
                    ['name' => 'Simon le Zélote', 'slug' => 'simon-le-zelote'],
                    ['name' => 'Judas Iscariot', 'slug' => 'judas-iscariot'],
                    ['name' => 'Matthias', 'slug' => 'matthias'],
                    ['name' => 'Paul de Tarse', 'slug' => 'paul-de-tarse'],
                ],
            ],
            [
                'slug' => 'femmes',
                'label' => 'Femmes de la Bible',
                'position' => 6,
                'people' => [
                    ['name' => 'Ève', 'slug' => 'eve'],
                    ['name' => 'Sara', 'slug' => 'sara'],
                    ['name' => 'Rebecca', 'slug' => 'rebecca'],
                    ['name' => 'Rachel', 'slug' => 'rachel'],
                    ['name' => 'Léa', 'slug' => 'lea'],
                    ['name' => 'Miriam', 'slug' => 'miriam'],
                    ['name' => 'Débora', 'slug' => 'debora'],
                    ['name' => 'Ruth', 'slug' => 'ruth'],
                    ['name' => 'Esther', 'slug' => 'esther'],
                    ['name' => 'Marie, mère de Jésus', 'slug' => 'marie-mere-de-jesus'],
                    ['name' => 'Marie-Madeleine', 'slug' => 'marie-madeleine'],
                    ['name' => 'Marthe de Béthanie', 'slug' => 'marthe-de-bethanie'],
                ],
            ],
        ];
    }
}
