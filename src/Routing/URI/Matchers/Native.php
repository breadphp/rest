<?php
namespace Bread\REST\Routing\URI\Matchers;

use Bread\REST\Routing\URI\Interfaces\Matcher;

class Native implements Matcher
{

    const MODIFIERS = '/imxs';

    const ZERO_OR_ONE = '?';

    const ONE_OR_MORE = '+';

    const ZERO_OR_MORE = '*';

    protected $template;

    protected $variables = array();

    protected $exclude = '[^\+\/#\?&;]*';

    protected $prefixes = array(
        ".",
        "+",
        "/",
        "#",
        "?",
        "&",
        ";"
    );

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function match($uri, &$matches = array())
    {
        $regex = array();
        $backrefs = array();
        preg_match_all("/(?<path>[^{]*)(?:{(?<pattern>[^}]*)})?/", $this->template, $matches, PREG_SET_ORDER);
        foreach ($matches as $i => $match) {
            if (isset($match['path'])) {
                $regex[] = preg_quote($match['path'], '/');
            }
            if (isset($match['pattern'])) {
                $this->variables[] = trim($match['pattern'], $this->exclude);
                $regex[] = $this->explodeGroup($match['pattern'], $backrefs);
            }
        }
        $regex = "^" . implode("", $regex) . '$';
        $matched = preg_match("/$regex/", $uri, $matches);
        $matches = array_intersect_key($matches, array_flip($this->variables));
        return $matched;
    }

    protected function explodeGroup($pattern, &$backrefs)
    {
        $rules = array();
        $vars = explode(",", $pattern);
        foreach ($vars as $var) {
            preg_match_all("/^(?<prefix>\.|\+|\/|#|\?|&|;)?(?<var>\w+)?(?<suffix>\*|\:\d+)?$/", $var, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                break;
            }
            $variable = isset($matches[0]['var']) ? $matches[0]['var'] : null;
            $type = isset($this->variables[$variable]) ? trim($this->variables[$variable], static::MODIFIERS) : $this->exclude;
            $rules[$variable]['prefix'] = isset($matches[0]['prefix']) ? $matches[0]['prefix'] : null;
            $rules[$variable]['suffix'] = isset($matches[0]['suffix']) ? $matches[0]['suffix'] : null;
            $rules[$matches[0]['var']]['var'] = isset($backrefs[$variable]) ? "\\k<$variable>" : "?<$variable>$type";
            $backrefs[$variable] = $variable;
        }
        return $this->getRegex($rules);
    }

    protected function getRegex($rules)
    {
        $regex = "";
        foreach ($rules as $var => $rule) {
            $prefix = $rule['prefix'];
            $var = $rule['var'];
            $suffix = $rule['suffix'];
            $regex .= $this->readSuffix($prefix, $var, $suffix);
        }
        return $regex;
    }

    protected function readSuffix($prefix, $var, $suffix)
    {
        $regex = "";
        switch ($suffix) {
            case '*':
                $regex .= $this->readPrefix($prefix, $var, true);
                break;
            case NULL:
                $regex .= $this->readPrefix($prefix, $var);
                break;
            default:
                $length = trim($suffix, ":");
                $regex .= $this->readPrefix($prefix, $var);
                break;
        }
        return $regex;
    }

    protected function readPrefix($prefix, $var, $multiple = false)
    {
        switch ($prefix) {
            case "/":
            case ".":
                $prefix = "\\$prefix";
            case "#":
            case "&":
            case "+":
            case "?":
            case ";":
                $sep = $prefix;
                break;
            default:
                $sep = '';
        }
        $multiplicity = $multiple ? self::ZERO_OR_MORE : self::ZERO_OR_ONE;
        $regex = "(?:$sep({$var}))$multiplicity";
        return $regex;
    }
}