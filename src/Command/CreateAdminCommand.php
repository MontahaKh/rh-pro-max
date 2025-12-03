<?php


namespace App\Command;

use App\Entity\User;
use App\Enum\UserRole;
// adapte le namespace si besoin
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create the default admin user if it does not already exist'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $passwordHasher
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $_ENV['DEFAULT_ADMIN_EMAIL'] ?? 'admin@example.com';
        $plainPassword = $_ENV['DEFAULT_ADMIN_PASSWORD'] ?? 'Admin123';

        $repo = $this->em->getRepository(User::class);

        // Vérifier si l'admin existe déjà
        $existing = $repo->findOneBy(['email' => $email]);
        if ($existing) {
            $output->writeln('<info>Admin already exists with email: ' . $email . '</info>');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRole(UserRole::ADMIN->value); // "ADMIN"


// ou ['ROLE_ADMIN', 'ROLE_USER'] selon ton appli

        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Admin created successfully!</info>');
        $output->writeln('<comment>Email: ' . $email . '</comment>');
        $output->writeln('<comment>Password: ' . $plainPassword . '</comment>');

        return Command::SUCCESS;
    }
}
