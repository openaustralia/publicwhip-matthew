<xsl:stylesheet version = '1.0' 
    xmlns:xsl='http://www.w3.org/1999/XSL/Transform'
    xmlns:html="http://www.w3.org/1999/xhtml">

<!-- Front page -->

<xsl:template match="publicwhip">
	<table class="wrans">
		<xsl:apply-templates select="wrans">
			<xsl:sort order="descending" select="stamp/@coldate"/>
		</xsl:apply-templates>
	</table>
</xsl:template>
 
<xsl:template match="wrans"> 
	<tr class="odd">
		<xsl:if test="position() mod 2 = 0">
			<xsl:attribute name="class">even</xsl:attribute>
		</xsl:if>
		<xsl:if test="position() mod 2 = 1">
			<xsl:attribute name="class">odd</xsl:attribute>
		</xsl:if>
		<td><xsl:value-of select="stamp/@coldate"/></td>
		<td>
			<div>
				<a>
					<xsl:attribute name="href">wrans.php?id=<xsl:value-of select="@id"/></xsl:attribute>
					<xsl:value-of select="@title"/>
				</a>
			</div>
		</td>
		<td><xsl:apply-templates select="speech[attribute::type='ques']"/></td>
		<td><xsl:apply-templates select="speech[attribute::type='reply']"/></td>
	</tr>
</xsl:template>
 
<xsl:template match="speech">
	<a>
		<xsl:attribute name="href">mp.php?id=<xsl:value-of select="@speakerid"/></xsl:attribute>
		<xsl:value-of select="@speakername"/>
	</a>
	<xsl:if test="not(position()=last())">, </xsl:if>
</xsl:template>

</xsl:stylesheet>

