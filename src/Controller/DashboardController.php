<?php


namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\JobOfferRepository;
use App\Repository\CandidateProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        // ✅ Redirection automatique selon le rôle
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        if ($this->isGranted('ROLE_HR_MANAGER')) {
            return $this->redirectToRoute('app_hr_dashboard');
        }
        if ($this->isGranted('ROLE_EMPLOYEE')) {
            return $this->redirectToRoute('app_employee_dashboard');
        }
        if ($this->isGranted('ROLE_CANDIDATE')) {
            return $this->redirectToRoute('app_candidate_dashboard');
        }

        // fallback
        return $this->redirectToRoute('app_home');
    }

    // ✅ Admin: stats globales
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(
        UserRepository             $userRepo,
        JobOfferRepository         $jobOfferRepo,
        CandidateProfileRepository $candidateProfileRepo
    ): Response
    {
        return $this->render('dashboard/admin.html.twig', [
            'users_count' => $userRepo->count([]),
            'offers_count' => $jobOfferRepo->count([]),
            'applications_count' => $candidateProfileRepo->count([]),
        ]);
    }

    // ✅ HR Manager: gérer offers + candidatures
    #[Route('/hr/dashboard', name: 'app_hr_dashboard')]
    #[IsGranted('ROLE_HR_MANAGER')]
    public function hr(JobOfferRepository $jobOfferRepo): Response
    {
        return $this->render('dashboard/hr.html.twig', [
            'job_offers' => $jobOfferRepo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    // ✅ Employee: profil + skills + candidatures internes
    #[Route('/employee/dashboard', name: 'app_employee_dashboard')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function employee(CandidateProfileRepository $candidateProfileRepo): Response
    {
        $user = $this->getUser();

        return $this->render('dashboard/employee.html.twig', [
            // si tu as internalApplicant dans CandidateProfile :
            'internal_apps' => $candidateProfileRepo->findBy(['internalApplicant' => $user], ['id' => 'DESC']),
        ]);
    }

    // ✅ Candidate: profil + CV + candidatures
    #[Route('/candidate/dashboard', name: 'app_candidate_dashboard')]
    #[IsGranted('ROLE_CANDIDATE')]
    public function candidate(CandidateProfileRepository $candidateProfileRepo): Response
    {
        $user = $this->getUser();

        return $this->render('dashboard/candidate.html.twig', [
            'applications' => $candidateProfileRepo->findBy(['candidate' => $user], ['id' => 'DESC']),
        ]);
    }
}
