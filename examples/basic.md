# XT basic syntax #

XT files are simple text files that are transformed to HTML, with a syntax
similar to Markdown, but can have PHP code just like a .php file.

Teste de lista [aqui](?test=lists) (código fonte [aqui](lists.xt)).

Teste de imagem dentro de link:

[![Teste](image.jpg "Caretinha!!")](image.jpg)

It uses the built-in processing that PHP does to .php files (interpret PHP code
inside php tags, just copy everything else to the output buffer), so .xt files  
must be files that can be used with the `include` construct, just like .php files.

If `allow_url_include` is enabled on php.ini, then you can use string streams as a source too.
	
The output produced by the first pass is captured and then parsed again, to translate the XT syntax to HTML,
so your PHP code can output text using either XT or HTML syntax. HTML code inside XT files is ignored and outputted without change
when it starts a new _scope_.

## Headers

Headers are lines that start from one to six `#`. Each `#` defines which header you want.

	# H1 Header
	
	## H2 Header
	
	### H3 Header ###
It doesn't matter the ending `#`, they'll be removed in the final HTML, but it may help you format your original text.

<div>
	<!-- até colocarmos um </ no inicio da identação atual,
	ou começarmos um parágrafo de texto XT (escrever algo na identação inicial sem começar como tag),
	o sistema só vai enviar o html diretamete para a saída -->
	<p>
		Teste de HTML dentro do XT.
	</p>
	<ul>
		<li>1</li>
		<li>2</li>
		<li>3</li>
	</ul></div>

---
#### Teste de UL e LI ####

-	um
-	dois
-	tres
	ainda no tres

	-	inline
	-	outro inline
		## Título dentro do item
		ainda no anterior
	
1.	numerico
2.	numerico
	ainda no segundo

	ainda no segundo
3.		function terceiro {

		código no terceiro
			teste
		}

> Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse nisl sem, rutrum ac lacus vel, 
> hendrerit venenatis felis. Phasellus ut aliquet erat, at egestas est. Nunc neque enim, euismod sed 
> erat sed, scelerisque dignissim nisl. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
> > Nested quote
>> with nested code
>> ~~~
>> test {
>> 	code;
>> }
>> ~~~
>> End of nested quote...
> Back to previous quote:
> 
> Nested list:
> - item 1
> - item 2
> - item 3
> - Ordered Nested List:
> 	1. one
>	2. two with code:
> 		~~~c++
> 		print("Hello World!");
> 		~~~
> 	3. three
> 
> In ut neque quis ex laoreet iaculis eget quis dolor. Donec maximus augue tellus, accumsan consequat risus 
> viverra condimentum. Donec imperdiet, quam sit amet mollis ultricies, tellus nibh imperdiet erat, vitae 
> convallis risus neque sed nulla. Sed vel lorem id enim faucibus dignissim nec a mauris. Nam mollis, 
> lacus id tincidunt pellentesque, quam felis porta urna, vel euismod lorem tortor non lorem. Duis sagittis 
> odio eget magna lacinia, vitae scelerisque leo ultrices.

	int i = 0;
	
	foreach ( k in 0..10 ) {
		print("Valor :", i + k);
	}
	
	echo AEEE PORRAA;

Morbi vitae elementum augue. Sed a justo ut felis egestas lobortis a ut lectus. Aliquam convallis tortor 
id massa venenatis cursus. Proin tincidunt semper bibendum. Aenean eleifend facilisis magna id euismod.
Sed eu felis ipsum. Fusce condimentum risus volutpat aliquam sodales.

## Second Level Heading ##

Meu e-mail: <ragatol@hotmail.com>

Cras sit amet efficitur mi. Nunc id aliquet nisl. Cras luctus odio sit amet arcu ultricies accumsan.
Sed purus sapien, sodales at mi id, scelerisque blandit neque. Interdum et malesuada fames ac ante ipsum 
primis in faucibus. Maecenas viverra vulputate leo. Etiam eu ipsum non leo pretium aliquam. Curabitur 
aliquet neque in est vulputate lacinia. Donec elementum, nisi vitae gravida consequat, libero leo ullamcorper 
dolor, sed vehicula magna metus et ipsum.

Sed tempus fermentum augue, eu vestibulum sapien tempus ut. Duis ac lectus lorem. Integer vel quam congue, 
mollis velit sed, finibus nulla. Fusce a semper enim. Maecenas consectetur elit vitae sem sollicitudin, 
vitae consectetur quam blandit. Nullam molestie ipsum quis libero pharetra cursus. Sed ut pharetra leo, 
in lacinia justo. Integer feugiat justo ac felis dictum, non lacinia ligula molestie. Proin sed velit eleifend, 
euismod est ut, pretium diam. Ut in dui est. Sed iaculis massa ac purus accumsan, in dapibus diam posuere.

### Third Level Heading

Etiam condimentum ipsum eget lorem auctor consequat. Phasellus tincidunt pellentesque efficitur. Integer malesuada 
auctor convallis. Nullam semper vulputate nunc ac sodales. Suspendisse potenti. Nam ultricies vehicula auctor.
Cras iaculis turpis et lorem ultrices, nec iaculis nibh rhoncus. Suspendisse sapien felis, dignissim ut arcu in, 
convallis mollis tellus. Aliquam vestibulum ullamcorper convallis. Donec ullamcorper magna nec mauris pharetra finibus. 