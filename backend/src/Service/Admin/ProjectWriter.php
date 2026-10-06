<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Dto\Request\Admin\ProjectPatchRequest;
use App\Dto\Request\Admin\ProjectRequest;
use App\Entity\Project;
use App\Entity\Technology;
use App\Repository\ProjectRepository;
use App\Repository\TechnologyRepository;
use App\Validation\InvalidRequest;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Applies admin requests to projects, including the checks that need the database.
 */
final readonly class ProjectWriter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projects,
        private TechnologyRepository $technologies,
    ) {
    }

    public function create(ProjectRequest $request): Project
    {
        $this->assertSlugIsFree($request->slug, null);

        $project = new Project($request->slug, $request->title, $request->description);
        $this->apply($project, $request);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    public function update(Project $project, ProjectRequest $request): void
    {
        $this->assertSlugIsFree($request->slug, $project);

        $project->setSlug($request->slug);
        $project->setTitle($request->title);
        $project->setDescription($request->description);
        $this->apply($project, $request);

        $this->entityManager->flush();
    }

    public function patch(Project $project, ProjectPatchRequest $request): void
    {
        if (null !== $request->isPublished) {
            $request->isPublished ? $project->publish() : $project->unpublish();
        }
        if (null !== $request->sortOrder) {
            $project->setSortOrder($request->sortOrder);
        }

        $this->entityManager->flush();
    }

    private function apply(Project $project, ProjectRequest $request): void
    {
        $project->setContent($request->content);
        $project->setRepoUrl($request->repoUrl);
        $project->setDemoUrl($request->demoUrl);
        $project->setSortOrder($request->sortOrder);
        $request->isPublished ? $project->publish() : $project->unpublish();

        $technologies = $this->resolveTechnologies($request->technologies);
        foreach ($project->getTechnologies()->toArray() as $current) {
            if (!\in_array($current, $technologies, true)) {
                $project->removeTechnology($current);
            }
        }
        foreach ($technologies as $technology) {
            $project->addTechnology($technology);
        }
    }

    private function assertSlugIsFree(string $slug, ?Project $current): void
    {
        $owner = $this->projects->findOneBy(['slug' => $slug]);
        if (null !== $owner && $owner !== $current) {
            throw InvalidRequest::at('slug', 'This slug is already used by another project.', $slug);
        }
    }

    /**
     * @param list<string> $slugs
     *
     * @return list<Technology>
     */
    private function resolveTechnologies(array $slugs): array
    {
        $bySlug = [];
        foreach ($this->technologies->findBy(['slug' => $slugs]) as $technology) {
            $bySlug[$technology->getSlug()] = $technology;
        }

        $resolved = [];
        foreach ($slugs as $index => $slug) {
            $resolved[] = $bySlug[$slug]
                ?? throw InvalidRequest::at(\sprintf('technologies[%d]', $index), \sprintf('Unknown technology "%s".', $slug), $slug);
        }

        return $resolved;
    }
}
