<?php

namespace Adeliom\EasyShopBundle\Command;

use Adeliom\EasyAdminUserBundle\Repository\UserRepository;
use Adeliom\EasyAdminUserBundle\Utils\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\Installer\Setup\ChannelSetupInterface;
use Sylius\Bundle\CoreBundle\Installer\Setup\CurrencySetupInterface;
use Sylius\Bundle\CoreBundle\Installer\Setup\LocaleSetupInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Webmozart\Assert\Assert;

final class SetupCommand extends Command
{
    protected static $defaultName = 'easyshop:install:setup';

    /** @var EntityManagerInterface */
    protected $em;
    /** @var UserPasswordHasherInterface */
    protected $passwordHasher;
    /** @var Validator */
    protected $validator;
    /** @var UserRepository */
    protected $adminUserRepository;
    /** @var CurrencySetupInterface */
    protected $currencySetup;
    /** @var LocaleSetupInterface */
    protected $localeSetup;
    /** @var ChannelSetupInterface */
    protected $channelSetup;
    /** @var UserRepositoryInterface */
    protected $shopUserRepository;
    /** @var EntityManagerInterface */
    protected $shopUserManager;
    /** @var FactoryInterface */
    protected $shopUserFactory;

    public function __construct(EntityManagerInterface $em,
                                UserPasswordHasherInterface $passwordHasher,
                                Validator $validator,
                                UserRepository $adminUserRepository,
                                CurrencySetupInterface $currencySetup,
                                LocaleSetupInterface $localeSetup,
                                ChannelSetupInterface $channelSetup,
                                UserRepositoryInterface $shopUserRepository,
                                EntityManagerInterface $shopUserManager,
                                FactoryInterface $shopUserFactory)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->adminUserRepository = $adminUserRepository;
        $this->currencySetup = $currencySetup;
        $this->localeSetup = $localeSetup;
        $this->channelSetup = $channelSetup;
        $this->shopUserRepository = $shopUserRepository;
        $this->shopUserManager = $shopUserManager;
        $this->shopUserFactory = $shopUserFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('EasyShop / Sylius configuration setup.')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command allows user to configure basic Sylius data.
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currency = $this->currencySetup->setup($input, $output, $this->getHelper('question'));
        $locale = $this->localeSetup->setup($input, $output, $this->getHelper('question'));
        $this->channelSetup->setup($locale, $currency);
        $this->setupAdministratorUser($input, $output, $locale->getCode());

        return 0;
    }

    protected function setupAdministratorUser(InputInterface $input, OutputInterface $output, string $localeCode): void
    {
        $outputStyle = new SymfonyStyle($input, $output);
        $outputStyle->writeln('Create your administrator account.');

        try {
            $user = $this->configureNewUser($input, $output);
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        $user->setEnabled(true);

        $this->em->persist($user);
        $this->em->flush();

        $outputStyle->writeln('<info>Administrator account successfully registered.</info>');
        $outputStyle->newLine();
    }

    private function configureNewUser(
        InputInterface $input,
        OutputInterface $output
    ) {
        // make sure to validate the user data is correct
        $adminUserClass = $this->adminUserRepository->getClassName();
        // create the user and hash its password
        $user = new $adminUserClass();
        $user->setRoles(['ROLE_ADMIN']);


        if ($input->getOption('no-interaction')) {
            Assert::null($this->adminUserRepository->findOneByEmail('sylius@example.com'));

            $user->setEmail('admin@example.com');
            $user->setUsername('admin');
            $password = 'admin';
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            return $user;
        }

        $user->setEmail($this->getAdministratorEmail($input, $output));
        $password = $this->getAdministratorPassword($input, $output);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        return $user;
    }

    private function createEmailQuestion(): Question
    {
        return (new Question('E-mail: '))
            ->setValidator(
            /**
             * @param mixed $value
             */
                function ($value): string {
                    return $this->validator->validateEmail((string) $value);
                }
            )
            ->setMaxAttempts(3)
            ;
    }

    private function getAdministratorEmail(InputInterface $input, OutputInterface $output): string
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        do {
            $question = $this->createEmailQuestion();
            $email = $questionHelper->ask($input, $output, $question);
            $exists = null !== $this->adminUserRepository->findOneByEmail($email);

            if ($exists) {
                $output->writeln('<error>E-Mail is already in use!</error>');
            }
        } while ($exists);

        return $email;
    }


    private function getAdministratorPassword(InputInterface $input, OutputInterface $output): string
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $validator = $this->getPasswordQuestionValidator();

        do {
            $passwordQuestion = $this->createPasswordQuestion('Choose password:', $validator);
            $confirmPasswordQuestion = $this->createPasswordQuestion('Confirm password:', $validator);

            $password = $questionHelper->ask($input, $output, $passwordQuestion);
            $repeatedPassword = $questionHelper->ask($input, $output, $confirmPasswordQuestion);

            if ($repeatedPassword !== $password) {
                $output->writeln('<error>Passwords do not match!</error>');
            }
        } while ($repeatedPassword !== $password);

        return $password;
    }

    private function getPasswordQuestionValidator(): \Closure
    {
        return
            /** @param mixed $value */
            function ($value): string {
                /** @var ConstraintViolationListInterface $errors */
                return $this->validator->validatePassword($value);
            }
            ;
    }

    private function createPasswordQuestion(string $message, \Closure $validator): Question
    {
        return (new Question($message))
            ->setValidator($validator)
            ->setMaxAttempts(3)
            ->setHidden(true)
            ->setHiddenFallback(false)
            ;
    }
}
