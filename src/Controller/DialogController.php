<?php

namespace MakinaCorpus\ULink\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\ULink\EntityFinderRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DialogController extends Controller
{
    /**
     * @return EntityFinderRegistry
     */
    private function getFinderRegistry()
    {
        return $this->get('ulink.finder.registry');
    }

    /**
     * Autocomplete callback
     */
    public function searchAction(Request $request, $string)
    {
        $ret = [];

        foreach ($this->getFinderRegistry()->all() as $searcher) {
            $local = $searcher->find($string, false, 16);
            if ($local) {
                foreach ($local as $result) {
                    $ret[] = [
                        'id'          => $result->getId(),
                        'type'        => $result->getType(),
                        'title'       => check_plain($result->getTitle()),
                        'group'       => check_plain($result->getGroup()),
                        'description' => check_plain($result->getDescription()),
                    ];
                }
            }
        }

        return new JsonResponse($ret);
    }

    /**
     * Dialog contents
     */
    public function dialogAction()
    {
        return new JsonResponse(['form' => $this->renderView('@ulink/views/dialog.html.twig')]);
    }
}
