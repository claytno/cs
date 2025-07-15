<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>

#[Route('<?= $route_path ?>')]
<?= $class_data->getClassDeclaration() ?>
{
<?= $generator->generateRouteForControllerMethod('', sprintf('%s_index', $route_name), ['GET', 'POST']) ?>
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

        if ($request->getMethod() == "POST") {
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($<?= $entity_var_singular ?>);
                $entityManager->flush();

                $this->addFlash(
                    "success",
                    "<?= $entity_var_singular ?> agregado con éxito"
                );

                return $this->redirectToRoute('<?= $route_name ?>_index', [], Response::HTTP_SEE_OTHER);
            }

            $this->addFlash(
                "error",
                "No se pudo agregar el <?= $entity_var_singular ?>"
            );

            return $this->redirectToRoute('<?= $route_name ?>_index');
        }

        $<?= $entity_var_plural ?> = $entityManager
            ->getRepository(<?= $entity_class_name ?>::class)
            ->findAll();

        return $this->render('<?= $templates_path ?>/index.html.twig', [
            '<?= $entity_twig_var_plural ?>' => $<?= $entity_var_plural ?>,
            'form' => $form,
        ]);
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}/edit', $entity_identifier), sprintf('%s_edit', $route_name), ['GET', 'POST']) ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>, ["action" => $this->generateUrl("<?= $route_name ?>_edit", ["id" => $<?= $entity_var_singular ?>->getId()])]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('<?= $route_name ?>_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('<?= $templates_path ?>/_form.html.twig', [
            '<?= $entity_twig_var_singular ?>' => $<?= $entity_var_singular ?>,
            'form' => $form,
        ]);
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_delete', $route_name), ['POST']) ?>
    public function delete(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$<?= $entity_var_singular ?>->get<?= ucfirst($entity_identifier) ?>(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($<?= $entity_var_singular ?>);
            $entityManager->flush();
        }

        return $this->redirectToRoute('<?= $route_name ?>_index', [], Response::HTTP_SEE_OTHER);
    }
}
