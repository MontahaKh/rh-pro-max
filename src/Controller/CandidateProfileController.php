<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Enum\CandidateStatus;
use App\Form\CandidateProfileType;
use App\Repository\CandidateProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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

    // ⭐ NOUVELLE ACTION : gérer la candidature + envoyer email
    #[Route('/{id}/manage', name: 'app_candidate_profile_manage', methods: ['GET', 'POST'])]
    public function manage(
        Request $request,
        CandidateProfile $candidateProfile,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $form = $this->createFormBuilder()
            ->add('status', EnumType::class, [
                'class' => CandidateStatus::class,
                'label' => 'New status',
            ])
            ->add('interviewAt', DateTimeType::class, [
                'label' => 'Interview date (optional)',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message to candidate (optional)',
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var CandidateStatus $status */
            $status = $data['status'];
            $interviewAt = $data['interviewAt'];
            $message = $data['message'] ?? '';

            // 1. Mettre à jour le statut
            $candidateProfile->setStatus($status);
            $entityManager->flush();

            // 2. Préparer l'email
            $jobTitle = $candidateProfile->getJobOffer()
                ? $candidateProfile->getJobOffer()->getTitle()
                : 'your application';

            if ($status === CandidateStatus::INTERVIEW) {
                $subject = 'Interview scheduled for ' . $jobTitle;

                $body = sprintf(
                    "<p>Hello %s,</p>
                     <p>Your application for the position <strong>%s</strong> has been selected for an interview.</p>",
                    $candidateProfile->getFullName(),
                    $jobTitle
                );

                if ($interviewAt) {
                    $body .= '<p>The interview is scheduled on <strong>' . $interviewAt->format('d/m/Y H:i') . '</strong>.</p>';
                }

                if (!empty($message)) {
                    $body .= '<p><strong>Additional message:</strong><br>' . nl2br($message) . '</p>';
                }

                $body .= '<p>Best regards,<br>Recruitment team</p>';
            } elseif ($status === CandidateStatus::REJECTED) {
                $subject = 'Your application for ' . $jobTitle;

                $body = sprintf(
                    "<p>Hello %s,</p>
                     <p>We are sorry to inform you that your application for the position <strong>%s</strong> has not been retained.</p>",
                    $candidateProfile->getFullName(),
                    $jobTitle
                );

                if (!empty($message)) {
                    $body .= '<p><strong>Message from recruiter:</strong><br>' . nl2br($message) . '</p>';
                }

                $body .= '<p>We thank you for your interest and wish you all the best in your future applications.</p>
                          <p>Best regards,<br>Recruitment team</p>';
            } else {
                $subject = 'Update about your application for ' . $jobTitle;

                $body = "<p>Hello " . $candidateProfile->getFullName() . ",</p>";

                if (!empty($message)) {
                    $body .= '<p>' . nl2br($message) . '</p>';
                } else {
                    $body .= '<p>Your application status has been updated: <strong>' . $status->value . '</strong>.</p>';
                }

                $body .= '<p>Best regards,<br>Recruitment team</p>';
            }

            // 3. Envoyer l'email
            if ($candidateProfile->getEmail()) {
                $email = (new Email())
                    ->from('no-reply@your-app.com') // à adapter
                    ->to($candidateProfile->getEmail())
                    ->subject($subject)
                    ->html($body);

                $mailer->send($email);
            }

            $this->addFlash('success', 'Candidate status updated and email sent.');

            return $this->redirectToRoute('app_candidate_profile_show', [
                'id' => $candidateProfile->getId(),
            ]);
        }

        return $this->render('candidate_profile/manage.html.twig', [
            'candidate_profile' => $candidateProfile,
            'form' => $form->createView(),
        ]);
    }
}
