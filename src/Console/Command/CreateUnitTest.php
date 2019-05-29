<?php
declare(strict_types=1);

namespace MyOnlineStore\PhpUnitSkeleton\Console\Command;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class CreateUnitTest extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('create')
            ->setDescription('Create a skeleton phpunit class with all constructor dependencies as mock')
            ->addArgument('filename', InputArgument::REQUIRED, 'input filename (relative path');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $input->getArgument('filename');
        $helper = $this->getHelper('question');
        $newFileName = null;

        if (false !== ($position = strpos($filePath, 'MyOnlineStore'))) {
            $newFileName = $filePath;
        }

        $newFileName = \str_replace('src/', 'tests/unit/', $newFileName);

        if (null === $newFileName) {
            throw new \InvalidArgumentException('no valid filename provided');
        }

        $somePieces = explode('.', $newFileName);
        $newFileName = sprintf('%sTest.%s', $somePieces[0], $somePieces[1]);

        if (\file_exists($newFileName)) {
            $question = new ConfirmationQuestion('existing unittest will be overwrite is this ok?', false);
        } else {
            $question = new ConfirmationQuestion(
                sprintf('Unit test will be created at %s is this ok?', $newFileName),
                false
            );
        }

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $astLocator = (new BetterReflection())->astLocator();
        $reflector = new ClassReflector(new SingleFileSourceLocator($filePath, $astLocator));

        $sourceClass = $reflector->getAllClasses()[0];

        $namespace = null;
        $className = $sourceClass->getShortName();
        $properties = $sourceClass->getImmediateProperties();
        $newNameSpace = null;
        $imports = [];

        foreach ($sourceClass->getDeclaringNamespaceAst() as $nodes) {
            if ($nodes instanceof Name) {
                $namespace = $nodes->parts;

                break;
            }
        }

        foreach ($sourceClass->getDeclaringNamespaceAst()->stmts as $statement) {
            if (!$statement instanceof Use_) {
                continue;
            }

            $imports[] = implode('\\', $statement->uses[0]->name->parts);
        }

        if (null === $namespace) {
            throw new \InvalidArgumentException('no valid namespace provided');
        }

        $lastPartOfNameSpace = $namespace;
        unset($lastPartOfNameSpace[0], $lastPartOfNameSpace[1]);
        $newNameSpace = implode('\\', array_merge([$namespace[0], 'Tests', $namespace[1]], $lastPartOfNameSpace));

        if (empty($newNameSpace)) {
            throw new \InvalidArgumentException('no valid namespace provided');
        }

        $template = sprintf("<?php\ndeclare(strict_types=1);\n\nnamespace %s;\n\n", $newNameSpace);
        $template .= sprintf("use %s;\n", $sourceClass->getName());

        foreach ($imports as $import) {
            $template .= sprintf("use %s;\n", $import);
        }

        $template .= "use PHPUnit\Framework\TestCase;\n";
        $template .= sprintf(
            "\nfinal class %sTest extends TestCase\n{\n",
            $sourceClass->getShortName()
        );

        foreach ($properties as $property) {
            $propertyName = 'public';

            if ($property->isPrivate()) {
                $propertyName = 'private';
            }

            if ($property->isProtected()) {
                $propertyName = 'protected';
            }

            $template .= sprintf(
                "    %s\n    %s $%s;\n\n",
                $property->getDocComment(),
                $propertyName,
                $property->getName()
            );
        }

        $template .= sprintf("    /**\n    * @var %s\n    */\n    private \$objectToUse;\n\n", $className);
        $template .= "    protected function setUp()\n    {\n";

        foreach ($properties as $property) {
            if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
                list(, $type) = $matches;
                $template .= sprintf(
                    '        $this->%s = $this->createMock(%s::class);%s',
                    $property->getName(),
                    $type,
                    "\n"
                );
            }
        }

        $propertiesCount = count($properties);

        if (0 !== $propertiesCount) {
            $template .= "\n";
        }

        $template .= sprintf("        \$this->objectToUse = new %s(", $className);

        if (0 === $propertiesCount) {
            $template .= ');';
        } else {
            $iteration = 1;
            $template .= "\n";

            foreach ($properties as $property) {
                $template .= sprintf('            $this->%s', $property->getName());

                if ($propertiesCount !== $iteration) {
                    $template .= ",\n";
                }

                $iteration++;
            }

            $template .= "\n        );";
        }

        $template .= "\n    }\n}";

        $dirName = \dirname($newFileName);

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
            chown($dirName, 1000);
        }

        $newFile = fopen($newFileName, 'wb');
        fwrite($newFile, $template);
        fclose($newFile);
    }
}
