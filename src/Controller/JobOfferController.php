<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\JobOffer;
use App\Entity\Candidate;
use App\Enum\CandidateStatus;
use App\Form\JobOfferType;
use App\Repository\CandidateProfileRepository;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/job-offer')]
class JobOfferController extends AbstractController
{
    // Public access - anyone can view job offers (including candidates)
    #[Route('/', name: 'app_job_offer_index', methods: ['GET'])]
    public function index(JobOfferRepository $jobOfferRepository): Response
    {
        return $this->render('job_offer/index.html.twig', [
            'job_offers' => $jobOfferRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    // Only HR Manager and Admin can create job offers
    #[Route('/new', name: 'app_job_offer_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_HR_MANAGER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jobOffer = new JobOffer();
        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobOffer->setCreator($this->getUser());
            $entityManager->persist($jobOffer);
            $entityManager->flush();

            $this->addFlash('success', 'Job offer created successfully!');

            return $this->redirectToRoute('app_job_offer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('job_offer/new.html.twig', [
            'job_offer' => $jobOffer,
            'form' => $form,
        ]);
    }

    // Public access - anyone can view a job offer detail
    // Public access - anyone can view a job offer detail
    #[Route('/{id}', name: 'app_job_offer_show', methods: ['GET'])]
    public function show(JobOffer $jobOffer, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $hasApplied = false;
        $user = $this->getUser();

        // إذا المستخدم Candidate خارجي
        if ($user instanceof Candidate) {
            $existingApplication = $candidateProfileRepository->findOneBy([
                'candidate' => $user,
                'jobOffer'  => $jobOffer
            ]);
            $hasApplied = $existingApplication !== null;
        }

        //  Employee  (App\Entity\User)
        if ($user instanceof \App\Entity\User) {
            $existingApplication = $candidateProfileRepository->findOneBy([
                'internalApplicant' => $user,
                'jobOffer'          => $jobOffer
            ]);
            $hasApplied = $existingApplication !== null;
        }

        return $this->render('job_offer/show.html.twig', [
            'job_offer'   => $jobOffer,
            'has_applied' => $hasApplied,
        ]);
    }


    // Apply to a job offer (for logged in candidates)
    #[Route('/{id}/apply', name: 'app_job_offer_apply', methods: ['POST'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function apply(Request $request, JobOffer $jobOffer, EntityManagerInterface $entityManager, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $candidate = $this->getUser();

        // Ensure the user is a Candidate
        if (!$candidate instanceof Candidate) {
            $this->addFlash('danger', 'Only candidates can apply for job offers.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        // Check if already applied
        $existingApplication = $candidateProfileRepository->findOneBy([
            'candidate' => $candidate,
            'jobOffer' => $jobOffer
        ]);

        if ($existingApplication) {
            $this->addFlash('warning', 'You have already applied for this job offer.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('apply'.$jobOffer->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        // Create candidate profile
        $candidateProfile = new CandidateProfile();
        $candidateProfile->setFullName($candidate->getFullName());
        $candidateProfile->setEmail($candidate->getEmail());
        $candidateProfile->setJobOffer($jobOffer);
        $candidateProfile->setCandidate($candidate);
        $candidateProfile->setStatus(CandidateStatus::NEW);

        $entityManager->persist($candidateProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Your application has been submitted successfully!');

        return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
    }



    ///
    // Apply to a job offer (for logged in employees)
    #[Route('/{id}/apply-internal', name: 'app_job_offer_apply_internal', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function applyInternal(
        Request $request,
        JobOffer $jobOffer,
        EntityManagerInterface $entityManager,
        CandidateProfileRepository $candidateProfileRepository
    ): Response {
        $employee = $this->getUser();

        // Ensure the user is an internal User (employee)
        if (!$employee instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Only employees can apply as internal applicants.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        // Check if already applied internally
        $existingApplication = $candidateProfileRepository->findOneBy([
            'internalApplicant' => $employee,
            'jobOffer'          => $jobOffer
        ]);

        if ($existingApplication) {
            $this->addFlash('warning', 'You have already applied for this job offer.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('apply_internal'.$jobOffer->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        // Create application (CandidateProfile) as internal applicant
        $candidateProfile = new CandidateProfile();

        // نحاولو نملّيو الاسم والا نرجعو للإيميل
        $fullName = trim(($employee->getFirstName() ?? '') . ' ' . ($employee->getLastName() ?? ''));
        if ($fullName === '') {
            $fullName = $employee->getEmail();
        }

        $candidateProfile->setFullName($fullName);
        $candidateProfile->setEmail($employee->getEmail());
        $candidateProfile->setJobOffer($jobOffer);
        $candidateProfile->setInternalApplicant($employee);
        $candidateProfile->setStatus(CandidateStatus::NEW);

        $entityManager->persist($candidateProfile);
        $entityManager->flush();

        $this->addFlash('success', 'Your internal application has been submitted successfully!');

        return $this->redirectToRoute('app_job_offer_show', ['id' => $jobOffer->getId()]);
    }





    // Only HR Manager and Admin can edit job offers
    #[Route('/{id}/edit', name: 'app_job_offer_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_HR_MANAGER')]
    public function edit(Request $request, JobOffer $jobOffer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Job offer updated successfully!');

            return $this->redirectToRoute('app_job_offer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('job_offer/edit.html.twig', [
            'job_offer' => $jobOffer,
            'form' => $form,
        ]);
    }

    // Only Admin can delete job offers
    #[Route('/{id}', name: 'app_job_offer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, JobOffer $jobOffer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jobOffer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($jobOffer);
            $entityManager->flush();
            $this->addFlash('success', 'Job offer deleted successfully!');
        }

        return $this->redirectToRoute('app_job_offer_index', [], Response::HTTP_SEE_OTHER);
    }
}
