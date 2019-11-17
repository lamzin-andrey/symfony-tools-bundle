<?php

/*
 * This file is part of the SymfonyToolsBundle package.
 *
 * (c) Lamzin Andrey <https://andryuxa.ru/>
 *
 * Freeware
 */

namespace Landlib\SymfonyToolsBundle;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Lamzin Andrey<lamzin.an@gmail.com>
 */
class SymfonyToolsBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
