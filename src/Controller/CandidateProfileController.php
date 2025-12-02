<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Form\CandidateProfileType;
use App\Repository\CandidateProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/profile')]
#[IsGranted('ROLE_RECRUITER')]
class CandidateProfileController extends AbstractController
{
    #[Route('/', name: 'app_candidate_profile_index', methods: ['GET'])]
    public function index(CandidateProfileRepository $candidateProfileRepository): Response
    {
        return $this->render('candidate_profile/index.html.twig', [
            'candidate_profiles' => $candidateProfileRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_candidate_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidateProfile = new CandidateProfile();
        $form = $this->createForm(CandidateProfileType::class, $candidateProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidateProfile);
            $entityManager->flush();

            return $this->redirectToRoute('app_candidate_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('candidate_profile/new.html.twig', [
            'candidate_profile' => $candidateProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_candidate_profile_show', methods: ['GET'])]
    public function show(CandidateProfile $candidateProfile): Response
    {
        return $this->render('candidate_profile/show.html.twig', [
            'candidate_profile' => $candidateProfile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidate_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CandidateProfile $candidateProfile, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidateProfileType::class, $candidateProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_candidate_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('candidate_profile/edit.html.twig', [
            'candidate_profile' => $candidateProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_candidate_profile_delete', methods: ['POST'])]
    public function delete(Request $request, CandidateProfile $candidateProfile, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidateProfile->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($candidateProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_candidate_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
