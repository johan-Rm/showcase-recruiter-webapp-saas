<?php

declare(strict_types=1);

namespace App\Doctrine\Function;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * `TEXT(expr)` en DQL → `CAST(expr AS text)` en SQL.
 *
 * POURQUOI — les compétences d'un profil vivent dans une colonne JSON, et Postgres n'a pas de
 * `lower(json)`. Sans conversion, il n'y a aucun moyen de chercher un mot-clé dedans depuis DQL,
 * qui ne connaît pas `CAST`. Une fonction d'une ligne évite d'ouvrir une requête native rien que
 * pour cela — et laisse la recherche dans le même QueryBuilder que tous les autres critères.
 *
 * Ce n'est PAS une bonne façon d'indexer une recherche : lire un JSON en texte interdit tout index
 * et impose un parcours complet. C'est acceptable sur le vivier actuel, ça ne le restera pas — la
 * recherche plein texte décidée pour ce projet (Postgres FTS + `pg_trgm`) reste à construire.
 */
final class TextFunction extends FunctionNode
{
    private ?Node $expression = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->expression = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'CAST('.$this->expression?->dispatch($sqlWalker).' AS text)';
    }
}
