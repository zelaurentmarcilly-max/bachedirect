<?php
/**
 * Barème d'exemple — version exemple-2026-11.
 *
 * Ces montants sont fictifs. Ils reprennent data/pricing.json pour que l'atelier
 * ait un jeu de nombres cohérent. Ce ne sont pas des prix de vente.
 *
 * Modifiez les valeurs ci-dessous. Le configurateur relit ce fichier à chaque
 * calcul. Gardez les clés : le moteur s'appuie dessus. Aucun appel fournisseur.
 *
 * Modes d'article :
 * - surface : max(plancher, largeur × hauteur × prix/m²) + forfait de finition
 *   + périmètre × prix au mètre ;
 * - preset : prix du format + écart de matière + finition ;
 * - length : max(plancher, longueur × prix/m). La hauteur est notée, pas facturée ;
 * - unit : prix du lot.
 *
 * Ensuite : majoration de délai, puis remise de quantité sur la pièce seulement
 * (dès 3 : × 0,94, dès 10 : × 0,88). Les accessoires suivent le délai, pas la remise.
 *
 * Le port d'exemple (shipping) sert à la méthode d'expédition « Port atelier ».
 * La TVA du tableau est indicative : WooCommerce applique ses propres taxes.
 *
 * @package BacheDirect
 */

defined('ABSPATH') || defined('BACHEDIRECT_STANDALONE') || exit;

return [
    'version' => 'exemple-2026-11',
    'currency' => 'EUR',
    'vatRate' => 0.2,
    'quantityBreaks' => [
        [
            'minQuantity' => 10,
            'factor' => 0.88,
        ],
        [
            'minQuantity' => 3,
            'factor' => 0.94,
        ],
    ],
    'leadTimes' => [
        'standard' => [
            'label' => 'Standard',
            'businessDays' => 5,
            'factor' => 1,
        ],
        'express' => [
            'label' => 'Express atelier',
            'businessDays' => 2,
            'factor' => 1.32,
        ],
    ],
    'shipping' => [
        'baseHt' => 12.9,
        'perKgHt' => 0.7,
        'freeFromGoodsHt' => 280,
        'country' => 'FR',
    ],
    'materials' => [
        'vinyle-510' => [
            'label' => 'Vinyle 510 g/m²',
            'note' => 'Toile blanche, usage extérieur',
            'kind' => 'area',
            'pricePerM2' => 24,
            'minHt' => 39,
            'weightPerM2' => 0.51,
        ],
        'vinyle-440' => [
            'label' => 'Vinyle 440 g/m²',
            'note' => 'Plus légère, pour intérieur et courte durée',
            'kind' => 'area',
            'pricePerM2' => 19.5,
            'minHt' => 34,
            'weightPerM2' => 0.44,
        ],
        'mesh-270' => [
            'label' => 'Mesh 270 g/m²',
            'note' => 'Micro-perforé, laisse passer l\'air',
            'kind' => 'area',
            'pricePerM2' => 21,
            'minHt' => 42,
            'weightPerM2' => 0.27,
        ],
        'mesh-340' => [
            'label' => 'Mesh 340 g/m²',
            'note' => 'Maille plus couvrante',
            'kind' => 'area',
            'pricePerM2' => 26.5,
            'minHt' => 48,
            'weightPerM2' => 0.34,
        ],
        'textile-220' => [
            'label' => 'Polyester 220 g/m²',
            'note' => 'Tombé souple, peu de reflet',
            'kind' => 'area',
            'pricePerM2' => 28,
            'minHt' => 45,
            'weightPerM2' => 0.22,
        ],
        'textile-occultant' => [
            'label' => 'Polyester occultant',
            'note' => 'Dos assombri si une lumière est placée derrière',
            'kind' => 'area',
            'pricePerM2' => 34,
            'minHt' => 52,
            'weightPerM2' => 0.28,
        ],
        'drapeau-110' => [
            'label' => 'Tricot 110 g/m²',
            'note' => 'Voile standard',
            'kind' => 'delta',
            'deltaHt' => 0,
            'extraWeightKg' => 0,
        ],
        'drapeau-maille' => [
            'label' => 'Maille drapeau 115 g/m²',
            'note' => 'Tombe plus droit en extérieur',
            'kind' => 'delta',
            'deltaHt' => 14,
            'extraWeightKg' => 0.15,
        ],
        'film-mat' => [
            'label' => 'Film mat',
            'note' => 'Moins de reflet en intérieur',
            'kind' => 'delta',
            'deltaHt' => 0,
            'extraWeightKg' => 0,
        ],
        'film-brillant' => [
            'label' => 'Film brillant',
            'note' => 'Couleurs plus vives',
            'kind' => 'delta',
            'deltaHt' => 8,
            'extraWeightKg' => 0,
        ],
        'rail-alu' => [
            'label' => 'Aluminium brut',
            'note' => 'Barre et coulisseaux',
            'kind' => 'delta',
            'deltaHt' => 0,
            'extraWeightKg' => 0,
        ],
        'textile-cadre' => [
            'label' => 'Textile cadre 260 g/m²',
            'note' => 'Tendu sur le pourtour',
            'kind' => 'delta',
            'deltaHt' => 0,
            'extraWeightKg' => 0,
        ],
        'selon-reference' => [
            'label' => 'Selon la référence',
            'note' => 'Pas de variante de matière',
            'kind' => 'delta',
            'deltaHt' => 0,
            'extraWeightKg' => 0,
        ],
    ],
    'finishes' => [
        'oeillets-50' => [
            'label' => 'Œillets tous les 50 cm',
            'note' => 'Tous les côtés',
            'flatHt' => 4.5,
            'perMeterHt' => 2.8,
        ],
        'oeillets-100' => [
            'label' => 'Œillets tous les 100 cm',
            'note' => 'Tous les côtés, pas plus large',
            'flatHt' => 4.5,
            'perMeterHt' => 1.6,
        ],
        'ourlet' => [
            'label' => 'Ourlet renforcé',
            'note' => 'Repli cousu, sans œillet',
            'flatHt' => 3,
            'perMeterHt' => 1.9,
        ],
        'fourreau' => [
            'label' => 'Fourreau haut et bas',
            'note' => 'Tunnel pour barre ou câble',
            'flatHt' => 6,
            'perMeterHt' => 3.4,
        ],
        'coupe-vive' => [
            'label' => 'Coupe à vif',
            'note' => 'Bord tranché, sans renfort',
            'flatHt' => 0,
            'perMeterHt' => 0,
        ],
        'fourreau-mat' => [
            'label' => 'Fourreau de mât',
            'note' => 'Inclus dans l\'oriflamme',
            'flatHt' => 0,
            'perMeterHt' => 0,
        ],
        'cassette-standard' => [
            'label' => 'Cassette standard',
            'note' => 'Pied et enrouleur d\'atelier',
            'flatHt' => 0,
            'perMeterHt' => 0,
        ],
        'cassette-renforcee' => [
            'label' => 'Cassette renforcée',
            'note' => 'Pied plus stable',
            'flatHt' => 22,
            'perMeterHt' => 0,
        ],
        'coulisseaux' => [
            'label' => 'Coulisseaux fournis',
            'note' => 'Inclus avec le rail',
            'flatHt' => 0,
            'perMeterHt' => 0,
        ],
        'barre-velcro' => [
            'label' => 'Jonc périphérique',
            'note' => 'Inclus avec le cadre',
            'flatHt' => 0,
            'perMeterHt' => 0,
        ],
        'pret' => [
            'label' => 'Prêt à l\'emploi',
            'note' => 'Pas de façonnage supplémentaire',
            'flatHt' => 0,
            'perMeterHt' => 0,
        ],
    ],
    'accessories' => [
        'sandow-10' => [
            'label' => 'Sandows (lot de 10)',
            'unitHt' => 11.5,
            'weightKg' => 0.18,
        ],
        'crochet-10' => [
            'label' => 'Crochets plats (lot de 10)',
            'unitHt' => 8.4,
            'weightKg' => 0.12,
        ],
        'lest-6' => [
            'label' => 'Sac de lest 6 kg',
            'unitHt' => 16,
            'weightKg' => 6,
        ],
        'housse' => [
            'label' => 'Housse de transport',
            'unitHt' => 14,
            'weightKg' => 0.35,
        ],
    ],
    'products' => [
        'bache-vinyle' => [
            'label' => 'Bâche vinyle',
            'mode' => 'surface',
            'minWidthCm' => 30,
            'maxWidthCm' => 500,
            'minHeightCm' => 30,
            'maxHeightCm' => 300,
            'defaultWidthCm' => 200,
            'defaultHeightCm' => 100,
            'defaultMaterialId' => 'vinyle-510',
            'defaultFinishId' => 'oeillets-50',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'vinyle-510',
                'vinyle-440',
            ],
            'finishes' => [
                'oeillets-50',
                'oeillets-100',
                'ourlet',
                'fourreau',
                'coupe-vive',
            ],
            'accessories' => [
                'sandow-10',
                'crochet-10',
                'lest-6',
            ],
        ],
        'bache-mesh' => [
            'label' => 'Bâche mesh',
            'mode' => 'surface',
            'minWidthCm' => 50,
            'maxWidthCm' => 500,
            'minHeightCm' => 50,
            'maxHeightCm' => 300,
            'defaultWidthCm' => 300,
            'defaultHeightCm' => 150,
            'defaultMaterialId' => 'mesh-270',
            'defaultFinishId' => 'oeillets-50',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'mesh-270',
                'mesh-340',
            ],
            'finishes' => [
                'oeillets-50',
                'ourlet',
                'coupe-vive',
            ],
            'accessories' => [
                'sandow-10',
                'crochet-10',
            ],
        ],
        'bache-textile' => [
            'label' => 'Bâche textile',
            'mode' => 'surface',
            'minWidthCm' => 40,
            'maxWidthCm' => 400,
            'minHeightCm' => 40,
            'maxHeightCm' => 250,
            'defaultWidthCm' => 200,
            'defaultHeightCm' => 150,
            'defaultMaterialId' => 'textile-220',
            'defaultFinishId' => 'ourlet',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'textile-220',
                'textile-occultant',
            ],
            'finishes' => [
                'ourlet',
                'fourreau',
                'coupe-vive',
            ],
            'accessories' => [
                'crochet-10',
                'housse',
            ],
        ],
        'oriflamme-goutte' => [
            'label' => 'Oriflamme goutte',
            'mode' => 'preset',
            'defaultPresetId' => 'compacte',
            'defaultMaterialId' => 'drapeau-110',
            'defaultFinishId' => 'fourreau-mat',
            'defaultLeadTimeId' => 'standard',
            'presets' => [
                'compacte' => [
                    'label' => 'Compacte 65 × 200 cm',
                    'widthCm' => 65,
                    'heightCm' => 200,
                    'unitHt' => 76,
                    'weightKg' => 2.2,
                ],
                'atelier' => [
                    'label' => 'Atelier 75 × 280 cm',
                    'widthCm' => 75,
                    'heightCm' => 280,
                    'unitHt' => 98,
                    'weightKg' => 2.8,
                ],
                'haute' => [
                    'label' => 'Haute 85 × 400 cm',
                    'widthCm' => 85,
                    'heightCm' => 400,
                    'unitHt' => 132,
                    'weightKg' => 3.6,
                ],
            ],
            'materials' => [
                'drapeau-110',
                'drapeau-maille',
            ],
            'finishes' => [
                'fourreau-mat',
            ],
            'accessories' => [
                'lest-6',
                'housse',
            ],
        ],
        'oriflamme-rectangle' => [
            'label' => 'Oriflamme rectangle',
            'mode' => 'preset',
            'defaultPresetId' => 'r70',
            'defaultMaterialId' => 'drapeau-110',
            'defaultFinishId' => 'fourreau-mat',
            'defaultLeadTimeId' => 'standard',
            'presets' => [
                'r70' => [
                    'label' => '70 × 180 cm',
                    'widthCm' => 70,
                    'heightCm' => 180,
                    'unitHt' => 72,
                    'weightKg' => 2,
                ],
                'r80' => [
                    'label' => '80 × 200 cm',
                    'widthCm' => 80,
                    'heightCm' => 200,
                    'unitHt' => 84,
                    'weightKg' => 2.3,
                ],
                'r90' => [
                    'label' => '90 × 300 cm',
                    'widthCm' => 90,
                    'heightCm' => 300,
                    'unitHt' => 118,
                    'weightKg' => 3.1,
                ],
            ],
            'materials' => [
                'drapeau-110',
                'drapeau-maille',
            ],
            'finishes' => [
                'fourreau-mat',
            ],
            'accessories' => [
                'lest-6',
                'housse',
            ],
        ],
        'oriflamme-plume' => [
            'label' => 'Oriflamme plume',
            'mode' => 'preset',
            'defaultPresetId' => 'p50',
            'defaultMaterialId' => 'drapeau-110',
            'defaultFinishId' => 'fourreau-mat',
            'defaultLeadTimeId' => 'standard',
            'presets' => [
                'p50' => [
                    'label' => '50 × 200 cm',
                    'widthCm' => 50,
                    'heightCm' => 200,
                    'unitHt' => 74,
                    'weightKg' => 1.9,
                ],
                'p60' => [
                    'label' => '60 × 260 cm',
                    'widthCm' => 60,
                    'heightCm' => 260,
                    'unitHt' => 96,
                    'weightKg' => 2.4,
                ],
                'p70' => [
                    'label' => '70 × 340 cm',
                    'widthCm' => 70,
                    'heightCm' => 340,
                    'unitHt' => 124,
                    'weightKg' => 3,
                ],
            ],
            'materials' => [
                'drapeau-110',
                'drapeau-maille',
            ],
            'finishes' => [
                'fourreau-mat',
            ],
            'accessories' => [
                'lest-6',
                'housse',
            ],
        ],
        'rollup' => [
            'label' => 'Roll-up',
            'mode' => 'preset',
            'defaultPresetId' => '85',
            'defaultMaterialId' => 'film-mat',
            'defaultFinishId' => 'cassette-standard',
            'defaultLeadTimeId' => 'standard',
            'presets' => [
                '85' => [
                    'label' => '85 × 200 cm',
                    'widthCm' => 85,
                    'heightCm' => 200,
                    'unitHt' => 89,
                    'weightKg' => 3.4,
                ],
                '100' => [
                    'label' => '100 × 200 cm',
                    'widthCm' => 100,
                    'heightCm' => 200,
                    'unitHt' => 104,
                    'weightKg' => 4.1,
                ],
                '120' => [
                    'label' => '120 × 200 cm',
                    'widthCm' => 120,
                    'heightCm' => 200,
                    'unitHt' => 126,
                    'weightKg' => 5,
                ],
                '150' => [
                    'label' => '150 × 200 cm',
                    'widthCm' => 150,
                    'heightCm' => 200,
                    'unitHt' => 158,
                    'weightKg' => 6.6,
                ],
            ],
            'materials' => [
                'film-mat',
                'film-brillant',
            ],
            'finishes' => [
                'cassette-standard',
                'cassette-renforcee',
            ],
            'accessories' => [
                'housse',
            ],
        ],
        'systeme-rail' => [
            'label' => 'Rail de suspension',
            'mode' => 'length',
            'pricePerM' => 18,
            'minHt' => 28,
            'weightPerM' => 0.42,
            'minWidthCm' => 80,
            'maxWidthCm' => 400,
            'minHeightCm' => 40,
            'maxHeightCm' => 300,
            'defaultWidthCm' => 200,
            'defaultHeightCm' => 100,
            'defaultMaterialId' => 'rail-alu',
            'defaultFinishId' => 'coulisseaux',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'rail-alu',
            ],
            'finishes' => [
                'coulisseaux',
            ],
            'accessories' => [
                'crochet-10',
            ],
        ],
        'systeme-cadre' => [
            'label' => 'Cadre autoportant',
            'mode' => 'preset',
            'defaultPresetId' => 'c80',
            'defaultMaterialId' => 'textile-cadre',
            'defaultFinishId' => 'barre-velcro',
            'defaultLeadTimeId' => 'standard',
            'presets' => [
                'c80' => [
                    'label' => '80 × 200 cm',
                    'widthCm' => 80,
                    'heightCm' => 200,
                    'unitHt' => 149,
                    'weightKg' => 7.5,
                ],
                'c100' => [
                    'label' => '100 × 200 cm',
                    'widthCm' => 100,
                    'heightCm' => 200,
                    'unitHt' => 176,
                    'weightKg' => 8.4,
                ],
                'c100h' => [
                    'label' => '100 × 240 cm',
                    'widthCm' => 100,
                    'heightCm' => 240,
                    'unitHt' => 198,
                    'weightKg' => 9.2,
                ],
            ],
            'materials' => [
                'textile-cadre',
            ],
            'finishes' => [
                'barre-velcro',
            ],
            'accessories' => [
                'housse',
            ],
        ],
        'accessoire-sandow' => [
            'label' => 'Sandows',
            'mode' => 'unit',
            'accessoryId' => 'sandow-10',
            'defaultMaterialId' => 'selon-reference',
            'defaultFinishId' => 'pret',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'selon-reference',
            ],
            'finishes' => [
                'pret',
            ],
            'accessories' => [],
        ],
        'accessoire-crochet' => [
            'label' => 'Crochets plats',
            'mode' => 'unit',
            'accessoryId' => 'crochet-10',
            'defaultMaterialId' => 'selon-reference',
            'defaultFinishId' => 'pret',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'selon-reference',
            ],
            'finishes' => [
                'pret',
            ],
            'accessories' => [],
        ],
        'accessoire-lest' => [
            'label' => 'Sac de lest',
            'mode' => 'unit',
            'accessoryId' => 'lest-6',
            'defaultMaterialId' => 'selon-reference',
            'defaultFinishId' => 'pret',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'selon-reference',
            ],
            'finishes' => [
                'pret',
            ],
            'accessories' => [],
        ],
        'accessoire-housse' => [
            'label' => 'Housse de transport',
            'mode' => 'unit',
            'accessoryId' => 'housse',
            'defaultMaterialId' => 'selon-reference',
            'defaultFinishId' => 'pret',
            'defaultLeadTimeId' => 'standard',
            'materials' => [
                'selon-reference',
            ],
            'finishes' => [
                'pret',
            ],
            'accessories' => [],
        ],
    ],
    'rejections' => [
        [
            'productId' => 'bache-mesh',
            'finishId' => 'fourreau',
            'message' => 'Le fourreau n\'est pas proposé sur le mesh : la toile ajourée ne tient pas un tunnel propre.',
        ],
    ],
];
