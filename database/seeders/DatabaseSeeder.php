<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Faq;
use App\Models\ForumBadge;
use App\Models\ForumCategory;
use App\Models\ForumCourse;
use App\Models\ForumReply;
use App\Models\ForumTag;
use App\Models\ForumTopic;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\LessonProgress;
use App\Models\News;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage users', 'manage teachers', 'manage students', 'manage courses',
            'manage certificates', 'manage forums', 'manage chats', 'manage reports',
            'configure system', 'teach courses', 'enroll courses', 'watch lessons',
            'take exams', 'issue certificates', 'participate forums', 'participate chats',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $adminRole = Role::findOrCreate('administrador');
        $teacherRole = Role::findOrCreate('professor');
        $studentRole = Role::findOrCreate('aluno');

        $adminRole->syncPermissions($permissions);
        $teacherRole->syncPermissions(['teach courses', 'manage courses', 'manage forums', 'participate chats', 'manage reports']);
        $studentRole->syncPermissions(['enroll courses', 'watch lessons', 'take exams', 'issue certificates', 'participate forums', 'participate chats']);

        $admin = User::firstOrCreate(['email' => 'admin@itapevi.sp.leg.br'], ['name' => 'Administrador EPI', 'password' => Hash::make('password')]);
        $teacher = User::firstOrCreate(['email' => 'professor@itapevi.sp.leg.br'], ['name' => 'Professor EPI', 'password' => Hash::make('password')]);
        $student = User::firstOrCreate(['email' => 'aluno@itapevi.sp.leg.br'], ['name' => 'Aluno EPI', 'password' => Hash::make('password')]);

        $admin->assignRole($adminRole);
        $teacher->assignRole($teacherRole);
        $student->assignRole($studentRole);

        $category = Category::firstOrCreate(
            ['slug' => 'poder-legislativo'],
            ['name' => 'Poder Legislativo', 'description' => 'Cursos sobre processo legislativo e cidadania.']
        );

        foreach (['Formação Política', 'Administração Pública', 'Cidadania', 'Gestão Pública', 'Cursos Internos'] as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        $course = Course::updateOrCreate(
            ['slug' => 'poder-legislativo-municipal'],
            [
                'category_id' => $category->id,
                'teacher_id' => $teacher->id,
                'name' => 'Poder Legislativo Municipal',
                'short_description' => 'Fundamentos da atuação parlamentar municipal para cidadãos e servidores.',
                'description' => 'Curso introdutório sobre câmara municipal, processo legislativo, fiscalização e participação social.',
                'workload_hours' => 20,
                'minimum_grade' => 7,
                'minimum_progress_percent' => 75,
                'status' => 'published',
                'is_featured' => true,
            ]
        );

        $module = CourseModule::updateOrCreate(
            ['course_id' => $course->id, 'position' => 1],
            ['title' => 'Módulo 1 - Fundamentos', 'status' => 'published', 'is_available' => true]
        );

        $lesson = Lesson::updateOrCreate(
            ['course_module_id' => $module->id, 'position' => 1],
            ['title' => 'O papel da Câmara Municipal', 'content_type' => 'youtube', 'content_url' => 'https://www.youtube.com/', 'duration_minutes' => 35, 'is_required' => true, 'is_available' => true]
        );

        ForumCourse::updateOrCreate(
            ['course_id' => $course->id],
            ['default_sections' => ['Dúvidas Gerais', 'Material Complementar', 'Debates', 'Exercícios']]
        );

        foreach ([
            ['Fórum geral institucional', 'institutional', 'Comunicados, avisos, eventos e notícias acadêmicas.'],
            ['Dúvidas Gerais', 'course', 'Perguntas sobre conteúdos, atividades e andamento do curso.'],
            ['Material Complementar', 'course', 'Links, PDFs e referências para aprofundamento.'],
            ['Debates', 'course', 'Discussão orientada e aprendizagem colaborativa.'],
            ['Exercícios', 'course', 'Apoio para atividades e tópicos avaliativos.'],
        ] as [$name, $type, $description]) {
            ForumCategory::updateOrCreate(
                ['course_id' => $course->id, 'name' => $name],
                ['type' => $type, 'description' => $description, 'slug' => Str::slug($name)]
            );
        }

        foreach (['legislacao', 'cidadania', 'orcamento', 'constituicao', 'politica-publica'] as $tag) {
            ForumTag::firstOrCreate(['slug' => $tag], ['name' => Str::title(str_replace('-', ' ', $tag))]);
        }

        foreach ([
            ['Primeira Participação', 'primeira-participacao', 'ri-seedling-line', 0],
            ['Aluno Participativo', 'aluno-participativo', 'ri-user-heart-line', 50],
            ['Debatedor', 'debatedor', 'ri-discuss-line', 120],
            ['Especialista', 'especialista', 'ri-medal-line', 250],
            ['Mentor da Comunidade', 'mentor-da-comunidade', 'ri-shield-star-line', 500],
            ['Professor Destaque', 'professor-destaque', 'ri-presentation-line', 150],
        ] as [$name, $slug, $icon, $points]) {
            ForumBadge::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'points_required' => $points]
            );
        }

        CertificateTemplate::updateOrCreate(
            ['name' => 'Modelo institucional'],
            ['body_html' => 'Certificamos que {{ aluno }} concluiu {{ curso }}.', 'is_default' => false]
        );
        foreach ([
            ['Modelo institucional verde', 'Layout oficial com identidade EAD EPI, moldura verde, QR Code e assinatura institucional.', true],
            ['Modelo solene legislativo', 'Modelo para cursos formais, com destaque para carga horária, conclusão e validação pública.', false],
            ['Modelo trilha rápida', 'Modelo compacto para oficinas, palestras e capacitações de curta duração.', false],
        ] as [$name, $body, $isDefault]) {
            CertificateTemplate::updateOrCreate(
                ['name' => $name],
                ['body_html' => $body, 'is_default' => $isDefault]
            );
        }
        Banner::firstOrCreate(['title' => 'Escola do Parlamento de Itapevi'], ['subtitle' => 'Educação legislativa, cidadania e gestão pública.', 'is_active' => true]);
        News::firstOrCreate(['slug' => 'plataforma-ead-epi'], ['author_id' => $admin->id, 'title' => 'Nova plataforma EAD da Escola do Parlamento', 'excerpt' => 'Ambiente digital para cursos, certificados e comunicação acadêmica.', 'body' => 'Conteúdo institucional da notícia.', 'published_at' => now()]);
        Faq::firstOrCreate(['question' => 'Como emitir certificado?'], ['answer' => 'Conclua o curso, alcance a nota mínima e acesse a área de certificados.']);
        Setting::firstOrCreate(['key' => 'institution.name'], ['group' => 'institution', 'value' => ['text' => 'Escola do Parlamento de Itapevi']]);
        Setting::updateOrCreate(
            ['key' => 'mail.smtp'],
            [
                'group' => 'mail',
                'value' => [
                    'enabled' => true,
                    'mailer' => 'log',
                    'host' => 'smtp.seu-servidor.com',
                    'port' => 587,
                    'username' => 'ead@itapevi.sp.leg.br',
                    'password' => '',
                    'scheme' => 'tls',
                    'from_address' => 'ead@itapevi.sp.leg.br',
                    'from_name' => 'EAD EPI - Escola do Parlamento',
                ],
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'mail.template'],
            [
                'group' => 'mail',
                'value' => [
                    'brand_name' => 'EAD EPI',
                    'subtitle' => 'Escola do Parlamento de Itapevi',
                    'primary_color' => '#008f43',
                    'accent_color' => '#ed1c24',
                    'footer_text' => 'Escola do Parlamento de Itapevi - Aprender, participar e transformar.',
                    'logo_url' => url('/assets/logo_escola.png'),
                ],
            ]
        );

        Enrollment::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $student->id],
            ['status' => 'completed', 'source' => 'manual', 'progress_percent' => 100, 'final_grade' => 8.5, 'enrolled_at' => now()->subDays(12), 'completed_at' => now()->subDay()]
        );

        LessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'user_id' => $student->id],
            ['watched_seconds' => 2100, 'progress_percent' => 100, 'is_completed' => true, 'last_accessed_at' => now()->subDay()]
        );

        $exam = Exam::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Avaliação final'],
            ['course_module_id' => $module->id, 'description' => 'Verificação de aprendizagem do módulo inicial.', 'minimum_grade' => 7, 'max_attempts' => 2, 'is_active' => true]
        );

        $question = Question::updateOrCreate(
            ['exam_id' => $exam->id, 'statement' => 'Qual é uma função essencial da Câmara Municipal?'],
            ['category_id' => $category->id, 'type' => 'multiple_choice', 'difficulty' => 'easy', 'subject' => 'Poder Legislativo', 'weight' => 1, 'correct_answer' => 'Fiscalizar e legislar no âmbito municipal.']
        );

        foreach ([
            ['A', 'Fiscalizar e legislar no âmbito municipal.', true],
            ['B', 'Julgar crimes federais.', false],
            ['C', 'Nomear ministros de Estado.', false],
        ] as [$label, $text, $isCorrect]) {
            QuestionOption::updateOrCreate(
                ['question_id' => $question->id, 'label' => $label],
                ['text' => $text, 'is_correct' => $isCorrect]
            );
        }

        Certificate::updateOrCreate(
            ['code' => 'CERT-DEMO-001'],
            [
                'course_id' => $course->id,
                'user_id' => $student->id,
                'verification_hash' => hash('sha256', 'CERT-DEMO-001'),
                'student_name' => $student->name,
                'course_name' => $course->name,
                'workload_hours' => $course->workload_hours,
                'completed_at' => now()->subDay()->toDateString(),
                'issued_at' => now()->subDay(),
                'status' => 'valid',
            ]
        );

        $forumCategory = ForumCategory::where('course_id', $course->id)->where('name', 'Debates')->first();
        $topic = ForumTopic::updateOrCreate(
            ['course_id' => $course->id, 'title' => 'Como a Câmara Municipal participa da cidadania?'],
            [
                'forum_category_id' => $forumCategory?->id,
                'user_id' => $student->id,
                'body' => 'Quais exemplos práticos mostram a relação entre Câmara Municipal e participação cidadã?',
                'status' => 'resolved',
                'type' => 'discussion',
                'views_count' => 12,
                'replies_count' => 1,
                'last_activity_at' => now()->subHours(6),
            ]
        );

        $reply = ForumReply::updateOrCreate(
            ['forum_topic_id' => $topic->id, 'user_id' => $teacher->id],
            ['body' => 'Um exemplo é a audiência pública, que aproxima moradores, vereadores e decisões sobre políticas locais.', 'is_accepted' => true]
        );
        $topic->forceFill(['accepted_reply_id' => $reply->id])->save();

        $room = ChatRoom::updateOrCreate(
            ['course_id' => $course->id, 'name' => 'Aluno/professor - Poder Legislativo Municipal'],
            ['type' => 'direct']
        );
        $room->participants()->firstOrCreate(['user_id' => $teacher->id]);
        $room->participants()->firstOrCreate(['user_id' => $student->id]);
        $room->messages()->updateOrCreate(
            ['user_id' => $student->id, 'body' => 'Professor, posso usar audiência pública como exemplo no fórum?'],
            ['sent_at' => now()->subHours(3)]
        );
        $room->messages()->updateOrCreate(
            ['user_id' => $teacher->id, 'body' => 'Pode sim. Relacione o exemplo com participação social e fiscalização.'],
            ['sent_at' => now()->subHours(2)]
        );

        $reviewCourse = Course::updateOrCreate(
            ['slug' => 'etica-e-transparencia-publica'],
            [
                'category_id' => $category->id,
                'teacher_id' => $teacher->id,
                'name' => 'Ética e Transparência Pública',
                'short_description' => 'Curso demo aguardando revisão do gestor.',
                'description' => 'Conteúdo de demonstração para validar o fluxo de revisão e aprovação.',
                'workload_hours' => 12,
                'minimum_grade' => 7,
                'minimum_progress_percent' => 75,
                'status' => 'pending_review',
                'submitted_for_review_at' => now()->subHours(4),
            ]
        );

        $reviewModule = CourseModule::updateOrCreate(
            ['course_id' => $reviewCourse->id, 'position' => 1],
            ['title' => 'Módulo 1 - Conduta pública', 'status' => 'published', 'is_available' => true]
        );
        Lesson::updateOrCreate(
            ['course_module_id' => $reviewModule->id, 'position' => 1],
            ['title' => 'Princípios de transparência', 'content_type' => 'pdf', 'duration_minutes' => 25, 'is_required' => true, 'is_available' => true]
        );

        $reviewExam = Exam::updateOrCreate(
            ['course_id' => $reviewCourse->id, 'title' => 'Avaliação diagnóstica'],
            ['course_module_id' => $reviewModule->id, 'minimum_grade' => 7, 'max_attempts' => 1, 'is_active' => true]
        );
        $reviewQuestion = Question::updateOrCreate(
            ['exam_id' => $reviewExam->id, 'statement' => 'Qual princípio orienta a transparência pública?'],
            ['category_id' => $category->id, 'type' => 'multiple_choice', 'difficulty' => 'easy', 'subject' => 'Transparência Pública', 'weight' => 1, 'correct_answer' => 'Publicidade dos atos públicos.']
        );
        foreach ([
            ['A', 'Publicidade dos atos públicos.', true],
            ['B', 'Sigilo permanente de toda informação.', false],
            ['C', 'Ausência de prestação de contas.', false],
        ] as [$label, $text, $isCorrect]) {
            QuestionOption::updateOrCreate(
                ['question_id' => $reviewQuestion->id, 'label' => $label],
                ['text' => $text, 'is_correct' => $isCorrect]
            );
        }

        $extraTeachers = collect([
            ['name' => 'Mariana Costa', 'email' => 'mariana.costa@itapevi.sp.leg.br', 'phone' => '(11) 4143-2101'],
            ['name' => 'Roberto Almeida', 'email' => 'roberto.almeida@itapevi.sp.leg.br', 'phone' => '(11) 4143-2102'],
        ])->map(function (array $data) use ($teacherRole) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole($teacherRole);
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'],
                    'city' => 'Itapevi',
                    'state' => 'SP',
                    'lgpd_consent_at' => now()->subDays(35),
                    'terms_accepted_at' => now()->subDays(35),
                    'lgpd_consent_version' => '2026.1',
                    'privacy_policy_version' => '2026.1',
                ]
            );

            return $user;
        });

        $studentSeeds = [
            ['name' => 'Ana Beatriz Martins', 'email' => 'ana.martins@aluno.epi.test', 'document' => '11122233344', 'city' => 'Itapevi'],
            ['name' => 'Bruno Henrique Souza', 'email' => 'bruno.souza@aluno.epi.test', 'document' => '22233344455', 'city' => 'Jandira'],
            ['name' => 'Camila Ferreira Lima', 'email' => 'camila.lima@aluno.epi.test', 'document' => '33344455566', 'city' => 'Barueri'],
            ['name' => 'Diego Oliveira Ramos', 'email' => 'diego.ramos@aluno.epi.test', 'document' => '44455566677', 'city' => 'Itapevi'],
            ['name' => 'Fernanda Nunes Rocha', 'email' => 'fernanda.rocha@aluno.epi.test', 'document' => '55566677788', 'city' => 'Osasco'],
            ['name' => 'João Pedro Carvalho', 'email' => 'joao.carvalho@aluno.epi.test', 'document' => '66677788899', 'city' => 'Itapevi'],
            ['name' => 'Luciana Pereira Gomes', 'email' => 'luciana.gomes@aluno.epi.test', 'document' => '77788899900', 'city' => 'Carapicuíba'],
        ];

        $demoStudents = collect($studentSeeds)->map(function (array $data, int $index) use ($studentRole) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole($studentRole);
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'document' => $data['document'],
                    'phone' => sprintf('(11) 9%04d-%04d', 7100 + $index, 2200 + $index),
                    'birthdate' => now()->subYears(22 + $index)->subMonths($index)->toDateString(),
                    'city' => $data['city'],
                    'state' => 'SP',
                    'lgpd_consent_at' => now()->subDays(20 - min($index, 10)),
                    'terms_accepted_at' => now()->subDays(20 - min($index, 10)),
                    'lgpd_consent_version' => '2026.1',
                    'privacy_policy_version' => '2026.1',
                ]
            );

            return $user;
        });

        $allTeachers = collect([$teacher])->merge($extraTeachers)->values();
        $allStudents = collect([$student])->merge($demoStudents)->values();
        $categoryBySlug = Category::all()->keyBy('slug');

        $demoCourses = [
            [
                'slug' => 'orcamento-publico-e-controle-social',
                'name' => 'Orçamento Público e Controle Social',
                'category' => 'administracao-publica',
                'teacher' => 1,
                'hours' => 24,
                'featured' => true,
                'short' => 'Entenda PPA, LDO, LOA e como a sociedade acompanha os gastos públicos.',
                'description' => 'Curso prático para leitura do orçamento municipal, fiscalização cidadã e acompanhamento de indicadores públicos.',
                'modules' => [
                    ['Planejamento orçamentário', ['PPA, LDO e LOA na prática', 'Como ler uma peça orçamentária']],
                    ['Execução e transparência', ['Portal da Transparência', 'Indicadores e prestação de contas']],
                    ['Controle social', ['Audiências públicas', 'Ferramentas para acompanhamento cidadão']],
                ],
            ],
            [
                'slug' => 'cidadania-e-participacao-popular',
                'name' => 'Cidadania e Participação Popular',
                'category' => 'cidadania',
                'teacher' => 2,
                'hours' => 18,
                'featured' => true,
                'short' => 'Formação para conselhos, audiências públicas, ouvidoria e participação comunitária.',
                'description' => 'Apresenta caminhos de participação popular e instrumentos de diálogo entre sociedade e poder público.',
                'modules' => [
                    ['Direitos e deveres', ['Cidadania no município', 'Participação e responsabilidade social']],
                    ['Canais de participação', ['Conselhos municipais', 'Ouvidoria e e-SIC']],
                    ['Projeto comunitário', ['Diagnóstico do território', 'Plano de ação local']],
                ],
            ],
            [
                'slug' => 'redacao-oficial-e-documentos-publicos',
                'name' => 'Redação Oficial e Documentos Públicos',
                'category' => 'cursos-internos',
                'teacher' => 0,
                'hours' => 16,
                'featured' => false,
                'short' => 'Ofícios, memorandos, atas e comunicação institucional com clareza e padronização.',
                'description' => 'Curso voltado a servidores e colaboradores que produzem documentos administrativos no cotidiano.',
                'modules' => [
                    ['Padrão de linguagem', ['Clareza, concisão e impessoalidade', 'Estrutura de documentos oficiais']],
                    ['Documentos administrativos', ['Ofícios e memorandos', 'Atas, relatórios e despachos']],
                ],
            ],
        ];

        foreach ($demoCourses as $courseIndex => $courseData) {
            $courseCategory = $categoryBySlug->get($courseData['category'], $category);
            $courseTeacher = $allTeachers->get($courseData['teacher'], $teacher);

            $demoCourse = Course::updateOrCreate(
                ['slug' => $courseData['slug']],
                [
                    'category_id' => $courseCategory->id,
                    'teacher_id' => $courseTeacher->id,
                    'name' => $courseData['name'],
                    'short_description' => $courseData['short'],
                    'description' => $courseData['description'],
                    'workload_hours' => $courseData['hours'],
                    'minimum_grade' => 7,
                    'minimum_progress_percent' => 75,
                    'seat_limit' => 60,
                    'status' => 'published',
                    'is_featured' => $courseData['featured'],
                    'starts_at' => now()->subDays(10 + $courseIndex)->toDateString(),
                    'ends_at' => now()->addDays(45 + ($courseIndex * 10))->toDateString(),
                ]
            );

            ForumCourse::updateOrCreate(
                ['course_id' => $demoCourse->id],
                ['default_sections' => ['Dúvidas Gerais', 'Material Complementar', 'Debates', 'Exercícios']]
            );

            foreach (['Dúvidas Gerais', 'Material Complementar', 'Debates', 'Exercícios'] as $section) {
                ForumCategory::updateOrCreate(
                    ['course_id' => $demoCourse->id, 'name' => $section],
                    ['type' => 'course', 'description' => "Espaço de {$section} do curso {$demoCourse->name}.", 'slug' => Str::slug($section)]
                );
            }

            $firstLesson = null;
            foreach ($courseData['modules'] as $moduleIndex => [$moduleTitle, $lessons]) {
                $demoModule = CourseModule::updateOrCreate(
                    ['course_id' => $demoCourse->id, 'position' => $moduleIndex + 1],
                    [
                        'title' => 'Módulo '.($moduleIndex + 1).' - '.$moduleTitle,
                        'description' => 'Conteúdos e atividades para '.$moduleTitle.'.',
                        'status' => 'published',
                        'is_available' => true,
                    ]
                );

                foreach ($lessons as $lessonIndex => $lessonTitle) {
                    $demoLesson = Lesson::updateOrCreate(
                        ['course_module_id' => $demoModule->id, 'position' => $lessonIndex + 1],
                        [
                            'title' => $lessonTitle,
                            'description' => 'Aula demonstrativa com material de apoio, exemplos e atividade de fixação.',
                            'content_type' => $lessonIndex % 2 === 0 ? 'youtube' : 'pdf',
                            'content_url' => $lessonIndex % 2 === 0 ? 'https://www.youtube.com/' : null,
                            'duration_minutes' => 20 + ($lessonIndex * 10) + ($moduleIndex * 5),
                            'is_required' => true,
                            'is_available' => true,
                        ]
                    );
                    $firstLesson ??= $demoLesson;

                    LessonMaterial::updateOrCreate(
                        ['lesson_id' => $demoLesson->id, 'title' => 'Material de apoio - '.$lessonTitle],
                        [
                            'file_path' => 'materials/demo/'.$demoCourse->slug.'-'.($moduleIndex + 1).'-'.($lessonIndex + 1).'.pdf',
                            'mime_type' => 'application/pdf',
                            'size_bytes' => 384000 + ($lessonIndex * 42000),
                            'downloads_count' => 3 + $lessonIndex + $moduleIndex,
                        ]
                    );
                }
            }

            $exam = Exam::updateOrCreate(
                ['course_id' => $demoCourse->id, 'title' => 'Avaliação final - '.$demoCourse->name],
                [
                    'course_module_id' => $demoCourse->modules()->orderByDesc('position')->first()?->id,
                    'description' => 'Avaliação automática para demonstração do fluxo acadêmico.',
                    'minimum_grade' => 7,
                    'time_limit_minutes' => 40,
                    'max_attempts' => 2,
                    'correction_type' => 'automatic',
                    'is_active' => true,
                ]
            );

            $question = Question::updateOrCreate(
                ['exam_id' => $exam->id, 'statement' => 'Qual atitude demonstra melhor aproveitamento do curso '.$demoCourse->name.'?'],
                [
                    'category_id' => $courseCategory->id,
                    'type' => 'multiple_choice',
                    'difficulty' => 'easy',
                    'subject' => $demoCourse->name,
                    'weight' => 1,
                    'correct_answer' => 'Aplicar o conteúdo em situações reais do município.',
                ]
            );

            foreach ([
                ['A', 'Aplicar o conteúdo em situações reais do município.', true],
                ['B', 'Memorizar termos sem relação com a prática.', false],
                ['C', 'Ignorar os materiais complementares.', false],
            ] as [$label, $text, $isCorrect]) {
                QuestionOption::updateOrCreate(
                    ['question_id' => $question->id, 'label' => $label],
                    ['text' => $text, 'is_correct' => $isCorrect]
                );
            }

            $courseStudents = $allStudents->slice($courseIndex * 2, 5)->values();
            if ($courseStudents->count() < 4) {
                $courseStudents = $allStudents->take(5)->values();
            }

            foreach ($courseStudents as $studentIndex => $demoStudent) {
                $completed = $studentIndex < 2;
                $progress = $completed ? 100 : [72, 45, 18][$studentIndex - 2] ?? 35;

                Enrollment::updateOrCreate(
                    ['course_id' => $demoCourse->id, 'user_id' => $demoStudent->id],
                    [
                        'status' => $completed ? 'completed' : 'active',
                        'source' => $studentIndex % 2 === 0 ? 'manual' : 'automatic',
                        'progress_percent' => $progress,
                        'final_grade' => $completed ? 8.2 + ($studentIndex * 0.4) : null,
                        'enrolled_at' => now()->subDays(18 - $studentIndex),
                        'completed_at' => $completed ? now()->subDays(3 + $studentIndex) : null,
                    ]
                );

                if ($firstLesson) {
                    LessonProgress::updateOrCreate(
                        ['lesson_id' => $firstLesson->id, 'user_id' => $demoStudent->id],
                        [
                            'watched_seconds' => $progress * 18,
                            'progress_percent' => min(100, $progress),
                            'is_completed' => $progress >= 100,
                            'last_accessed_at' => now()->subHours(4 + $studentIndex),
                        ]
                    );
                }

                if ($completed) {
                    $code = 'CERT-DEMO-'.strtoupper(substr(Str::slug($demoCourse->slug, ''), 0, 8)).'-'.str_pad((string) $demoStudent->id, 3, '0', STR_PAD_LEFT);
                    Certificate::updateOrCreate(
                        ['code' => $code],
                        [
                            'course_id' => $demoCourse->id,
                            'user_id' => $demoStudent->id,
                            'verification_hash' => hash('sha256', $code),
                            'student_name' => $demoStudent->name,
                            'course_name' => $demoCourse->name,
                            'workload_hours' => $demoCourse->workload_hours,
                            'completed_at' => now()->subDays(3 + $studentIndex)->toDateString(),
                            'issued_at' => now()->subDays(2 + $studentIndex),
                            'status' => 'valid',
                        ]
                    );
                }
            }

            $debateCategory = ForumCategory::where('course_id', $demoCourse->id)->where('name', 'Debates')->first();
            $forumAuthor = $courseStudents->first() ?? $student;
            $topic = ForumTopic::updateOrCreate(
                ['course_id' => $demoCourse->id, 'title' => 'Aplicações práticas de '.$demoCourse->name],
                [
                    'forum_category_id' => $debateCategory?->id,
                    'user_id' => $forumAuthor->id,
                    'body' => 'Quais exemplos reais podemos usar para aplicar este conteúdo na rotina da cidade?',
                    'status' => 'open',
                    'type' => 'discussion',
                    'views_count' => 24 + ($courseIndex * 7),
                    'replies_count' => 2,
                    'last_activity_at' => now()->subHours(2 + $courseIndex),
                ]
            );

            ForumReply::updateOrCreate(
                ['forum_topic_id' => $topic->id, 'user_id' => $courseTeacher->id],
                ['body' => 'Tragam exemplos do cotidiano da administração pública e relacionem com os conceitos do módulo.', 'is_accepted' => false]
            );
            ForumReply::updateOrCreate(
                ['forum_topic_id' => $topic->id, 'user_id' => ($courseStudents->get(1) ?? $student)->id],
                ['body' => 'Um exemplo interessante é comparar o conteúdo com uma audiência pública ou com dados do portal da transparência.', 'is_accepted' => false]
            );

            $room = ChatRoom::updateOrCreate(
                ['course_id' => $demoCourse->id, 'name' => 'Turma - '.$demoCourse->name],
                ['type' => 'course']
            );
            $room->participants()->firstOrCreate(['user_id' => $courseTeacher->id]);
            foreach ($courseStudents->take(4) as $participant) {
                $room->participants()->firstOrCreate(['user_id' => $participant->id]);
            }
            $room->messages()->updateOrCreate(
                ['user_id' => $courseTeacher->id, 'body' => 'Bom dia, turma! O material da semana já está disponível no ambiente.'],
                ['sent_at' => now()->subHours(8 + $courseIndex)]
            );
            $room->messages()->updateOrCreate(
                ['user_id' => $forumAuthor->id, 'body' => 'Professor, a atividade pode usar um caso real de Itapevi?'],
                ['sent_at' => now()->subHours(7 + $courseIndex)]
            );
            $room->messages()->updateOrCreate(
                ['user_id' => $courseTeacher->id, 'body' => 'Pode sim. Só lembrem de preservar dados pessoais e citar a fonte pública.'],
                ['sent_at' => now()->subHours(6 + $courseIndex)]
            );
        }

        News::updateOrCreate(
            ['slug' => 'novas-turmas-escola-parlamento'],
            [
                'author_id' => $admin->id,
                'title' => 'Novas turmas abertas na Escola do Parlamento',
                'excerpt' => 'Cursos de orçamento, cidadania e redação oficial ampliam a trilha de formação.',
                'body' => 'A plataforma EAD passa a contar com novas turmas demonstrativas, professores especialistas, fórum de debates e acompanhamento de progresso.',
                'published_at' => now()->subDays(2),
            ]
        );

        Faq::updateOrCreate(
            ['question' => 'Como acompanho meu progresso no curso?'],
            ['answer' => 'Acesse o painel do aluno, abra Meus Cursos e acompanhe aulas, materiais, avaliações e certificados.', 'group' => 'aluno', 'position' => 2, 'is_active' => true]
        );
    }
}
