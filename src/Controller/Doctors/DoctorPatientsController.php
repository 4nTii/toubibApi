<?php

namespace App\Controller\Doctors;

use App\Entity\Appointments;
use App\Entity\Doctors;
use App\Entity\PatientsHistory;
use App\Entity\Users;
use App\Repository\AppointmentsRepository;
use App\Repository\DoctorsRepository;
use App\Repository\PatientsHistoryRepository;
use App\Repository\UsersRepository;
use App\Service\Mailer\UserMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DoctorPatientsController extends AbstractController
{
    public function createPatient(
        Request $request,
        UsersRepository $usersRepository,
        DoctorsRepository $doctorsRepository,
        UserMailerService $mailer,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        foreach (['firstName', 'lastName', 'email', 'phone', 'gender'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['status' => false, 'message' => "Le champ « $field » est requis."], 400);
            }
        }

        if (!in_array($data['gender'], ['male', 'female'], true)) {
            return $this->json(['status' => false, 'message' => 'Genre invalide. Valeurs acceptées : male, female'], 400);
        }

        if ($usersRepository->findByEmail($data['email'])) {
            return $this->json(['status' => false, 'message' => 'Un compte avec cet email existe déjà.'], 409);
        }

        /** @var Users $connectedUser */
        $connectedUser = $this->getUser();
        $doctor        = $doctorsRepository->findOneBy(['user' => $connectedUser]);

        $user = new Users();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setPhone($data['phone']);
        $user->setGender($data['gender']);
        $user->setRole('ROLE_USER');
        $user->setIsActive(true);
        $user->setBiography('');
        $user->setForcePasswordChange(true);

        $plainPassword = 'Toubib@' . str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

        if ($doctor) {
            $user->setMainDoctor($doctor);
        }

        if (!empty($data['birthDay'])) {
            try {
                $user->setBirthDay(new \DateTime($data['birthDay']));
            } catch (\Exception) {}
        }

        $em->persist($user);
        $em->flush();

        $mailer->sendPatientCreationEmail($user, $plainPassword);

        return $this->json([
            'status'  => true,
            'message' => 'Patient créé avec succès.',
            'data'    => [
                'id'        => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
                'email'     => $user->getEmail(),
                'phone'     => $user->getPhone(),
                'gender'    => $user->getGender(),
            ],
        ], 201);
    }

    public function searchPatients(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $q = trim($request->query->getString('q', ''));

        if (strlen($q) < 2) {
            return $this->json(['status' => true, 'data' => []]);
        }

        $qb = $em->createQueryBuilder()
            ->select('u')
            ->from(Users::class, 'u')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults(10);

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('LOWER(u.firstName)', ':q'),
                $qb->expr()->like('LOWER(u.lastName)', ':q'),
                $qb->expr()->like('LOWER(u.email)', ':q'),
                $qb->expr()->like('u.phone', ':q')
            )
        )->setParameter('q', '%' . strtolower($q) . '%');

        /** @var Users[] $users */
        $users = $qb->getQuery()->getResult();

        $data = array_map(fn(Users $u) => [
            'id'        => $u->getId(),
            'firstName' => $u->getFirstName(),
            'lastName'  => $u->getLastName(),
            'email'     => $u->getEmail(),
            'phone'     => $u->getPhone(),
            'gender'    => $u->getGender(),
        ], $users);

        return $this->json(['status' => true, 'data' => $data]);
    }

    public function listPatients(
        Request $request,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 20)));

        $result = $usersRepository->findByMainDoctorPaginated($doctor, $page, $limit);

        $data = array_map(fn(Users $u) => [
            'id'              => $u->getId(),
            'firstName'       => $u->getFirstName(),
            'lastName'        => $u->getLastName(),
            'email'           => $u->getEmail(),
            'phone'           => $u->getPhone(),
            'gender'          => $u->getGender(),
            'birthDay'        => $u->getBirthDay()?->format('Y-m-d'),
            'address'         => $u->getAddress(),
            'socialNumber'    => $u->getSocialNumber(),
            'dateInscription' => $u->getDateInscription()->format('Y-m-d'),
        ], $result['data']);

        return $this->json([
            'status' => true,
            'data'   => [
                'patients'   => $data,
                'total'      => $result['total'],
                'page'       => $page,
                'totalPages' => $result['totalPages'],
            ],
        ]);
    }

    public function updatePatient(
        int $id,
        Request $request,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $patient = $usersRepository->find($id);
        if (!$patient || $patient->getMainDoctor()?->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Patient introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['status' => false, 'message' => 'Corps de requête invalide'], 400);
        }

        $allowedFields  = ['firstName', 'lastName', 'birthDay', 'address', 'socialNumber'];
        $nullableFields = ['address', 'birthDay', 'socialNumber'];

        $unexpected = array_diff(array_keys($data), $allowedFields);
        if (!empty($unexpected)) {
            return $this->json([
                'status'  => false,
                'message' => 'Champs non autorisés : ' . implode(', ', $unexpected),
            ], 400);
        }

        $setters = [
            'firstName'    => 'setFirstName',
            'lastName'     => 'setLastName',
            'birthDay'     => 'setBirthDay',
            'address'      => 'setAddress',
            'socialNumber' => 'setSocialNumber',
        ];

        foreach ($setters as $field => $setter) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];

            if (($value === null || $value === '') && !in_array($field, $nullableFields, true)) {
                return $this->json(['status' => false, 'message' => "Le champ '$field' ne peut pas être vide"], 400);
            }

            if ($field === 'birthDay' && is_string($value) && $value !== '') {
                try {
                    $value = new \DateTime($value);
                } catch (\Exception) {
                    return $this->json(['status' => false, 'message' => 'Format de date invalide (ex: 1990-05-21)'], 400);
                }
            }

            $patient->$setter($value === '' ? null : $value);
        }

        $em->flush();

        return $this->json([
            'status'  => true,
            'message' => 'Patient mis à jour avec succès',
            'data'    => [
                'id'           => $patient->getId(),
                'firstName'    => $patient->getFirstName(),
                'lastName'     => $patient->getLastName(),
                'birthDay'     => $patient->getBirthDay()?->format('Y-m-d'),
                'address'      => $patient->getAddress(),
                'socialNumber' => $patient->getSocialNumber(),
            ],
        ]);
    }

    public function removePatient(
        int $id,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $patient = $usersRepository->find($id);
        if (!$patient || $patient->getMainDoctor()?->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Patient introuvable'], 404);
        }

        $patient->setMainDoctor(null);
        $em->flush();

        return $this->json(['status' => true, 'message' => 'Patient retiré de votre liste']);
    }

    public function getPatientAppointments(
        int $id,
        Request $request,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository,
        AppointmentsRepository $appointmentsRepository
    ): JsonResponse {
        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $patient = $usersRepository->find($id);
        if (!$patient || $patient->getMainDoctor()?->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Patient introuvable'], 404);
        }

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));

        $result = $appointmentsRepository->findByPatientPaginated($patient, $page, $limit);

        $data = array_map(function (Appointments $a) {
            $apptDoctor = $a->getDoctor();
            $doctorUser = $apptDoctor?->getUser();
            $bs         = $a->getBusinessSite();
            return [
                'id'              => $a->getId(),
                'status'          => $a->getStatus(),
                'startTime'       => $a->getStartTime()->format('Y-m-d H:i'),
                'endTime'         => $a->getEndTime()->format('Y-m-d H:i'),
                'notes'           => $a->getNotes(),
                'doctorFirstName' => $doctorUser?->getFirstName(),
                'doctorLastName'  => $doctorUser?->getLastName(),
                'speciality'      => $apptDoctor?->getSpeciality()?->getName(),
                'businessSite'    => $bs?->getName(),
            ];
        }, $result['data']);

        return $this->json([
            'status' => true,
            'data'   => [
                'appointments' => $data,
                'total'        => $result['total'],
                'page'         => $page,
                'totalPages'   => $result['totalPages'],
            ],
        ]);
    }

    public function getPatientHistory(
        int $id,
        Request $request,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository,
        PatientsHistoryRepository $historyRepository
    ): JsonResponse {
        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $patient = $usersRepository->find($id);
        if (!$patient || $patient->getMainDoctor()?->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Patient introuvable'], 404);
        }

        $page  = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));

        $result = $historyRepository->findByUserPaginated($patient, $page, $limit);

        $data = array_map(function (PatientsHistory $h) {
            $hDoctor     = $h->getDoctor();
            $hDoctorUser = $hDoctor?->getUser();
            return [
                'id'              => $h->getId(),
                'date'            => $h->getDate()->format('Y-m-d H:i'),
                'notes'           => $h->getNotes(),
                'doctorFirstName' => $hDoctorUser?->getFirstName(),
                'doctorLastName'  => $hDoctorUser?->getLastName(),
                'speciality'      => $hDoctor?->getSpeciality()?->getName(),
            ];
        }, $result['data']);

        return $this->json([
            'status' => true,
            'data'   => [
                'history'    => $data,
                'total'      => $result['total'],
                'page'       => $page,
                'totalPages' => $result['totalPages'],
            ],
        ]);
    }

    public function addPatientHistory(
        int $id,
        Request $request,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable'], 404);
        }

        $patient = $usersRepository->find($id);
        if (!$patient || $patient->getMainDoctor()?->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Patient introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $notes = trim($data['notes'] ?? '');
        if ($notes === '') {
            return $this->json(['status' => false, 'message' => 'La note ne peut pas être vide'], 400);
        }

        $entry = (new PatientsHistory())
            ->setUser($patient)
            ->setDoctor($doctor)
            ->setNotes($notes);

        $em->persist($entry);
        $em->flush();

        $doctorUser = $doctor->getUser();

        return $this->json([
            'status'  => true,
            'message' => 'Note ajoutée avec succès',
            'data'    => [
                'id'              => $entry->getId(),
                'date'            => $entry->getDate()->format('Y-m-d H:i'),
                'notes'           => $entry->getNotes(),
                'doctorFirstName' => $doctorUser?->getFirstName(),
                'doctorLastName'  => $doctorUser?->getLastName(),
                'speciality'      => $doctor->getSpeciality()?->getName(),
            ],
        ]);
    }
}
