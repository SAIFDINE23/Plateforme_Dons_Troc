<?php

namespace App\Command;

use App\Entity\Annonce;
use App\Enum\AnnonceState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:annonces:archive-expired',
    description: 'Archive les annonces expirées (expiresAt dépassé)'
)]
class ArchiveExpiredAnnoncesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le nombre d\'annonces à archiver sans modifier la base')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limite le nombre d\'annonces traitées', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $dryRun = (bool) $input->getOption('dry-run');
        $limit = $input->getOption('limit');

        $qb = $this->em->getRepository(Annonce::class)->createQueryBuilder('a');
        $qb
            ->andWhere('a.expiresAt IS NOT NULL')
            ->andWhere('a.expiresAt <= :now')
            ->andWhere('a.state != :archived')
            ->setParameter('now', $now)
            ->setParameter('archived', AnnonceState::ARCHIVED)
            ->orderBy('a.expiresAt', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults((int) $limit);
        }

        $annonces = $qb->getQuery()->getResult();
        $count = count($annonces);

        if ($count === 0) {
            $io->success('Aucune annonce expirée à archiver.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->warning(sprintf('%d annonce(s) seraient archivées.', $count));
            return Command::SUCCESS;
        }

        foreach ($annonces as $annonce) {
            $annonce->setState(AnnonceState::ARCHIVED);
        }

        $this->em->flush();

        $io->success(sprintf('%d annonce(s) archivées.', $count));

        return Command::SUCCESS;
    }
}
