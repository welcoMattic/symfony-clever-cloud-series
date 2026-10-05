<?php

namespace App;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Le Schedule `default`, consommé par `messenger:consume scheduler_default`.
 *
 * Les tâches elles-mêmes sont déclarées au plus près du code, par l'attribut
 * `#[AsCronTask]` des commandes. Ce fournisseur ne porte que ce qui vaut pour
 * tout le Schedule : un verrou et un état partagés par toutes les instances.
 * Sans le verrou, chaque scaler joue chaque tâche. Sans état partagé, le
 * scaler qui reprend la main après un redémarrage rejoue ce qu'un autre a déjà
 * fait, ou oublie ce qui devait tourner pendant qu'il était arrêté.
 */
#[AsSchedule]
final class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        #[Target('cache.scheduler')]
        private readonly CacheInterface $cache,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->lock($this->lockFactory->createLock('scheduler_default'))
        ;
    }
}
