# 🏗️ Architecture CQS avec Repositories - Backend SSA

## 📋 **Vue d'ensemble**

Ce document décrit l'architecture CQS (Command Query Separation) refactorée du Backend SSA, utilisant des repositories personnalisés pour une meilleure séparation des responsabilités.

## 🎯 **Principes CQS**

### **Commands (Commandes)**
- **Responsabilité** : Modifier l'état de l'application
- **Retour** : `void` (pas de retour)
- **Exemples** : `CreateAgentCommand`, `UpdateMissionCommand`

### **Queries (Requêtes)**
- **Responsabilité** : Récupérer des données sans modification
- **Retour** : DTOs ou entités
- **Exemples** : `GetAgentsQuery`, `GetMissionQuery`

## 🏛️ **Architecture des Couches**

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│                   (Controllers)                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                         │
│              (Commands & Queries)                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Commands  │  │   Queries   │  │   Handlers  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Domain Layer                              │
│              (Entities & Services)                          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                Infrastructure Layer                          │
│              (Repositories & Persistence)                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Agent     │  │  Mission    │  │   Country   │        │
│  │ Repository  │  │ Repository  │  │ Repository  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 **Repositories Personnalisés**

### **AgentRepository**
```php
class AgentRepository extends ServiceEntityRepository
{
    public function findWithFilters(?string $status, ?int $countryId, int $page, int $limit): array
    public function findWithDetails(int $agentId): ?Agent
    public function findByCodeName(string $codeName): ?Agent
    public function findAvailableAgents(): array
    public function findByCountry(int $countryId): array
}
```

### **MissionRepository**
```php
class MissionRepository extends ServiceEntityRepository
{
    public function findWithFilters(?string $status, ?string $danger, ?int $countryId, int $page, int $limit): array
    public function findWithDetails(int $missionId): ?Mission
    public function findInProgressMissions(): array
    public function findByCountry(int $countryId): array
    public function findActiveMissions(): array
}
```

### **CountryRepository**
```php
class CountryRepository extends ServiceEntityRepository
{
    public function findAllWithAgents(): array
    public function findWithDetails(int $countryId): ?Country
    public function findByDangerLevel(string $dangerLevel): array
    public function findWithInfiltratedAgents(): array
    public function findWithoutInfiltratedAgents(): array
}
```

### **UserRepository**
```php
class UserRepository extends ServiceEntityRepository
{
    public function findByEmail(string $email): ?User
    public function findByVerificationToken(string $token): ?User
    public function findByRefreshToken(string $token): ?User
    public function findUnverifiedUsers(): array
    public function emailExists(string $email): bool
}
```

### **MessageRepository**
```php
class MessageRepository extends ServiceEntityRepository
{
    public function findByRecipient(Agent $recipient): array
    public function findBySender(Agent $sender): array
    public function findByRecipientAndSender(Agent $recipient, Agent $sender): array
    public function findUnreadByRecipient(Agent $recipient): array
    public function deleteAllByAgent(Agent $agent): int
    public function findWithPagination(int $page, int $limit, ?Agent $recipient = null): array
}
```

## 📊 **Avantages de cette Architecture**

### **✅ Séparation claire des responsabilités**
- **Commands** : Modifient l'état
- **Queries** : Récupèrent des données
- **Repositories** : Gèrent la persistance

### **✅ Réutilisabilité**
- Les repositories peuvent être utilisés par plusieurs handlers
- Logique de requête centralisée et testable

### **✅ Testabilité**
- Chaque couche peut être testée indépendamment
- Mocks faciles pour les repositories

### **✅ Maintenance**
- Code plus lisible et organisé
- Modifications localisées dans les repositories

### **✅ Performance**
- Requêtes optimisées dans les repositories
- Possibilité de cache et d'optimisations

## 🚀 **Utilisation dans les Handlers**

### **Query Handlers (avant)**
```php
// ❌ Ancienne approche - logique de requête dans le handler
$qb = $this->entityManager->createQueryBuilder();
$qb->select('a')
   ->from(Agent::class, 'a')
   ->leftJoin('a.infiltratedCountry', 'c');
// ... logique complexe de filtrage et pagination
```

### **Query Handlers (après)**
```php
// ✅ Nouvelle approche - logique déléguée au repository
$agents = $this->agentRepository->findWithFilters(
    $query->status,
    $query->countryId,
    $query->page,
    $query->limit
);
```

### **Command Handlers (avant)**
```php
// ❌ Ancienne approche - accès direct à l'EntityManager
$country = $this->entityManager->getRepository(Country::class)->find($command->countryId);
```

### **Command Handlers (après)**
```php
// ✅ Nouvelle approche - utilisation des repositories
$country = $this->countryRepository->find($command->countryId);
```

## 🔄 **Migration Progressive**

### **Phase 1 : Création des Repositories** ✅
- [x] AgentRepository
- [x] MissionRepository
- [x] CountryRepository
- [x] UserRepository

### **Phase 2 : Refactoring des Query Handlers** ✅
- [x] GetAgentsHandler
- [x] GetAgentHandler
- [x] GetMissionsHandler

### **Phase 3 : Refactoring des Command Handlers** ✅
- [x] CreateAgentHandler
- [x] CreateMissionHandler
- [x] UpdateAgentStatusHandler
- [x] UpdateMissionHandler
- [x] RegisterUserHandler
- [x] VerifyEmailHandler

### **Phase 4 : Refactoring des Services** ✅
- [x] CountryDangerLevelService
- [x] AgentStatusChangeService (déjà optimal)
- [x] MessageHandlers

### **Phase 5 : Tests et Optimisations** ⏳
- [ ] Tests unitaires des repositories
- [ ] Tests d'intégration
- [ ] Optimisations de performance

## 📝 **Bonnes Pratiques**

### **1. Naming Convention**
- **Commands** : `CreateAgentCommand`, `UpdateMissionCommand`
- **Queries** : `GetAgentsQuery`, `GetMissionQuery`
- **Handlers** : `CreateAgentHandler`, `GetAgentsHandler`
- **Repositories** : `AgentRepository`, `MissionRepository`

### **2. Structure des Repositories**
- Hériter de `ServiceEntityRepository`
- Méthodes publiques pour les requêtes complexes
- Utiliser `createQueryBuilder()` pour les requêtes personnalisées
- Grouper les méthodes par fonctionnalité

### **3. Gestion des Erreurs**
- Lever des exceptions métier (`DomainException`)
- Validation des paramètres dans les repositories
- Gestion des cas d'erreur dans les handlers

### **4. Performance**
- Utiliser des joins appropriés
- Implémenter la pagination dans les repositories
- Éviter les requêtes N+1

## 🔍 **Exemples d'Utilisation**

### **Dans un Controller**
```php
#[Route('/api/agents', methods: ['GET'])]
public function list(Request $request): JsonResponse
{
    $query = new GetAgentsQuery(
        $request->query->get('status'),
        $request->query->get('countryId'),
        (int) $request->query->get('page', 1),
        (int) $request->query->get('limit', 10)
    );
    
    $agents = $this->queryBus->dispatch($query);
    return $this->json($agents);
}
```

### **Dans un Repository**
```php
public function findWithFilters(?string $status, ?int $countryId, int $page, int $limit): array
{
    $qb = $this->createQueryBuilder('a')
        ->leftJoin('a.infiltratedCountry', 'c')
        ->leftJoin('a.mentor', 'm');

    // Filtres conditionnels
    if ($status !== null) {
        $qb->andWhere('a.status = :status')
           ->setParameter('status', AgentStatus::from($status));
    }

    // Pagination
    $offset = ($page - 1) * $limit;
    $qb->setFirstResult($offset)
       ->setMaxResults($limit);

    return $qb->getQuery()->getResult();
}
```

## 📚 **Ressources et Références**

- [Symfony Documentation - Repositories](https://symfony.com/doc/current/doctrine.html#querying-for-objects-the-repository)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

---

**Version** : 1.0  
**Dernière mise à jour** : Décembre 2024  
**Auteur** : Équipe SSA
