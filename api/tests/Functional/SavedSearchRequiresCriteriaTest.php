<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Une recherche sans aucun critère ne s'enregistre pas (task API-041).
 *
 * L'écran refusait déjà : son bouton reste éteint tant qu'aucun critère n'est posé. Mais un
 * garde-fou qui ne vit que dans le bouton se contourne par un appel direct, et une recherche sans
 * critère n'a aucun filtre — son jeu de résultats est le vivier entier, sans que rien ne la
 * distingue d'une recherche ordinaire.
 *
 * LA NUANCE QUI COMPTE, et qui se teste ici : « absent » ne veut pas dire « vide ». Sur une
 * modification, ne pas parler des critères signifie « n'y touche pas » et doit continuer de passer ;
 * seule une liste explicitement vide est refusée.
 */
final class SavedSearchRequiresCriteriaTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';

    private const array UN_CRITERE = [['facet' => 'sector', 'value' => 'Informatique', 'group' => 'metier']];

    public function testUneCreationSansCritereEstRefusee(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, ['criteria' => []]);

        self::assertResponseStatusCodeSame(422);
        // Le corps se lit ICI plutôt que par l'aide du socle : un refus de validation est servi en
        // `application/problem+json`, que celle-ci refuse. Et il se DÉCODE avant d'être comparé —
        // le JSON échappe les accents, une comparaison de texte brut ne les retrouverait pas.
        $refus = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('criteria', $refus['violations'][0]['propertyPath'], 'le refus nomme le champ');
        self::assertSame(
            'Ajoutez au moins un critère à votre recherche.',
            $refus['violations'][0]['message'],
            'le message convenu',
        );
    }

    public function testUneCreationAvecUnCriterePasseToujours(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, ['criteria' => self::UN_CRITERE]);

        self::assertResponseStatusCodeSame(201);
    }

    public function testViderLesCriteresDUneRechercheExistanteEstRefuse(): void
    {
        $token = $this->loginToken(self::OWNER);
        $this->request('POST', '/v1/searches', $token, ['criteria' => self::UN_CRITERE]);
        self::assertResponseStatusCodeSame(201);
        $id = $this->jsonResponse()['search']['id'];

        $this->request('PATCH', "/v1/searches/{$id}", $token, ['criteria' => []]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testUneModificationQuiNeParlePasDesCriteresPasse(): void
    {
        $token = $this->loginToken(self::OWNER);
        $this->request('POST', '/v1/searches', $token, ['criteria' => self::UN_CRITERE]);
        self::assertResponseStatusCodeSame(201);
        $id = $this->jsonResponse()['search']['id'];

        // « Absent » veut dire « inchangé » : renommer ne doit rien exiger des critères.
        $this->request('PATCH', "/v1/searches/{$id}", $token, ['name' => 'Renommée sans toucher aux critères']);

        self::assertResponseIsSuccessful();
    }
}
