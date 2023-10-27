<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class CommandeController extends AbstractController
{
    public function __construct(
        // Symfony will inject the 'blog_publishing' workflow configured before
        private readonly WorkflowInterface $invoiceStatusStateMachine,
        private readonly EntityManagerInterface $manager
    ) {}

    #[Route('/', name: 'app_commande')]
    public function index(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll()
        ]);
    }
    #[Route('/{id}', name: 'app_commande_details', requirements: ['id' => '\d+'])]
    public function details(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', ["commande" => $commande]);
    }


    #[Route('/{id}/{targetStatus}', name: 'app_commande_apply_workflow')]
    public function applyWorkflow(Commande $commande, string $targetStatus): RedirectResponse
    {
        if($this->invoiceStatusStateMachine->can($commande,$targetStatus)){
            $this->invoiceStatusStateMachine->apply($commande,$targetStatus);
            $this->manager->persist($commande);
            $this->manager->flush();
        }

        return $this->redirectToRoute('app_commande_details',['id'=>$commande->getId()]);
    }


    #[Route('/generate', name: 'app_commande_generate')]
    public function generate(EntityManagerInterface $manager): RedirectResponse
    {
        $faker = Factory::create('fr_FR');

        $commande = new Commande();
        $commande->setNumero($faker->randomNumber(6));
        $commande->setAdresse($faker->address());


        $manager->persist($commande);
        $manager->flush();

        $this->addFlash("success","Commande générée avec succès !");

        return $this->redirectToRoute('app_commande');
    }
}
